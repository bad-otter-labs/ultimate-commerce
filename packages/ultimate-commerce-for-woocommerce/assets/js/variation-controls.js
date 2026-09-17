( function () {
    'use strict';

    const FORM_SELECTOR = '[data-uc-variation-form="1"]';
    const OPTION_SELECTOR = '.uc-variation-option';

    function parseData( form ) {
        if ( form.ucVariationData ) {
            return form.ucVariationData;
        }
        const node = form.querySelector( '[data-uc-variation-data="1"]' );
        if ( ! node ) {
            return null;
        }
        try {
            form.ucVariationData = JSON.parse( node.textContent || '{}' );
        } catch ( error ) {
            form.ucVariationData = null;
        }
        return form.ucVariationData;
    }

    function attributeValue( variation, name ) {
        if ( ! variation || ! variation.attributes ) {
            return '';
        }
        return String( variation.attributes[ name ] || '' );
    }

    function matchesSelection( variation, selection ) {
        return Object.keys( selection ).every( function ( name ) {
            const selected = String( selection[ name ] || '' );
            const value = attributeValue( variation, name );
            return selected === '' || value === '' || value === selected;
        } );
    }

    function matchingVariation( data, selection ) {
        const complete = Object.keys( selection ).length > 0 && Object.keys( selection ).every( function ( name ) {
            return String( selection[ name ] || '' ) !== '';
        } );
        if ( ! complete ) {
            return null;
        }
        return ( data.variations || [] ).find( function ( variation ) {
            return matchesSelection( variation, selection );
        } ) || null;
    }

    function optionAvailable( data, selection, attribute, value ) {
        if ( data.availability_complete === false ) {
            return true;
        }
        const candidate = Object.assign( {}, selection, { [ attribute ]: value } );
        return ( data.variations || [] ).some( function ( variation ) {
            return Boolean( variation.purchasable && variation.in_stock && matchesSelection( variation, candidate ) );
        } );
    }

    function currentSelection( form ) {
        const selection = {};
        form.querySelectorAll( '[data-uc-attribute-input]' ).forEach( function ( input ) {
            selection[ input.getAttribute( 'data-uc-attribute-input' ) ] = input.value || '';
        } );
        return selection;
    }

    function attributeInput( form, attribute ) {
        return Array.from( form.querySelectorAll( '[data-uc-attribute-input]' ) ).find( function ( input ) {
            return input.getAttribute( 'data-uc-attribute-input' ) === attribute;
        } ) || null;
    }

    function nativeAttributeInput( form, attribute ) {
        return Array.from( form.querySelectorAll( '[data-uc-native-attribute-input]' ) ).find( function ( input ) {
            return input.getAttribute( 'data-uc-native-attribute-input' ) === attribute;
        } ) || null;
    }

    function setImage( form, media, fallbackSrc ) {
        const image = form.closest( '[data-uc-product-card="1"]' )?.querySelector( '.uc-product-card__image--primary' );
        if ( ! image ) {
            return;
        }
        if ( ! image.dataset.ucOriginalSrc ) {
            image.dataset.ucOriginalSrc = image.getAttribute( 'src' ) || '';
            image.dataset.ucOriginalSrcset = image.getAttribute( 'srcset' ) || '';
            image.dataset.ucOriginalSizes = image.getAttribute( 'sizes' ) || '';
            image.dataset.ucOriginalAlt = image.getAttribute( 'alt' ) || '';
        }
        if ( media && media.src ) {
            image.setAttribute( 'src', media.src );
            media.srcset ? image.setAttribute( 'srcset', media.srcset ) : image.removeAttribute( 'srcset' );
            media.sizes ? image.setAttribute( 'sizes', media.sizes ) : image.removeAttribute( 'sizes' );
            image.setAttribute( 'alt', media.alt || '' );
            return;
        }
        if ( fallbackSrc ) {
            image.setAttribute( 'src', fallbackSrc );
            image.removeAttribute( 'srcset' );
            image.removeAttribute( 'sizes' );
            return;
        }
        if ( image.dataset.ucOriginalSrc ) {
            image.setAttribute( 'src', image.dataset.ucOriginalSrc );
        }
        image.dataset.ucOriginalSrcset ? image.setAttribute( 'srcset', image.dataset.ucOriginalSrcset ) : image.removeAttribute( 'srcset' );
        image.dataset.ucOriginalSizes ? image.setAttribute( 'sizes', image.dataset.ucOriginalSizes ) : image.removeAttribute( 'sizes' );
        image.setAttribute( 'alt', image.dataset.ucOriginalAlt || '' );
    }

    function updatePrice( price, variation ) {
        if ( ! price ) {
            return;
        }
        if ( ! Object.prototype.hasOwnProperty.call( price.dataset, 'ucOriginalHtml' ) ) {
            price.dataset.ucOriginalHtml = price.innerHTML;
        }
        if ( variation && variation.price?.html ) {
            price.innerHTML = variation.price.html;
            return;
        }
        price.innerHTML = price.dataset.ucOriginalHtml || '';
    }

    function updateUi( form, swatchImage ) {
        const data = parseData( form );
        if ( ! data ) {
            return;
        }
        const selection = currentSelection( form );

        form.querySelectorAll( '[data-uc-attribute]' ).forEach( function ( group ) {
            const attribute = group.getAttribute( 'data-uc-attribute' ) || '';
            group.querySelectorAll( OPTION_SELECTOR ).forEach( function ( option ) {
                const value = option.getAttribute( 'data-uc-option' ) || '';
                const selected = String( selection[ attribute ] || '' ) === value;
                const available = optionAvailable( data, selection, attribute, value );
                option.setAttribute( 'aria-checked', selected ? 'true' : 'false' );
                option.setAttribute( 'aria-disabled', available ? 'false' : 'true' );
                option.disabled = ! available;
            } );
        } );

        const variation = matchingVariation( data, selection );
        const variationInput = form.querySelector( '[name="variation_id"]' );
        const submit = form.querySelector( '[data-uc-variation-submit="1"]' );
        const price = form.closest( '[data-uc-product-card="1"]' )?.querySelector( '[data-uc-price="1"]' );
        const usable = Boolean( variation && variation.purchasable && variation.in_stock );

        if ( variationInput ) {
            variationInput.value = variation ? String( variation.id || 0 ) : '0';
        }
        if ( submit ) {
            submit.disabled = ! usable;
            submit.textContent = usable
                ? String( data.labels?.add_to_cart || 'Add to cart' )
                : String( variation ? ( data.labels?.unavailable || 'Unavailable' ) : ( data.labels?.select_options || 'Select options' ) );
        }
        updatePrice( price, variation );
        setImage( form, variation?.media || null, variation ? '' : swatchImage );

        form.dispatchEvent( new CustomEvent( 'uc:variation-change', {
            bubbles: true,
            detail: {
                productId: Number( data.product_id || 0 ),
                selection: selection,
                variation: variation,
                addToCartEnabled: usable,
            },
        } ) );
    }

    function chooseOption( option ) {
        if ( option.disabled || option.getAttribute( 'aria-disabled' ) === 'true' ) {
            return;
        }
        const form = option.closest( FORM_SELECTOR );
        const group = option.closest( '[data-uc-attribute]' );
        if ( ! form || ! group ) {
            return;
        }
        const attribute = group.getAttribute( 'data-uc-attribute' ) || '';
        const input = attributeInput( form, attribute );
        const nativeInput = nativeAttributeInput( form, attribute );
        if ( ! input ) {
            return;
        }
        input.value = option.getAttribute( 'data-uc-option' ) || '';
        if ( nativeInput ) {
            nativeInput.value = option.getAttribute( 'data-uc-request-value' ) || input.value;
        }
        updateUi( form, option.getAttribute( 'data-uc-swatch-image' ) || '' );
    }

    document.addEventListener( 'click', function ( event ) {
        const option = event.target.closest?.( OPTION_SELECTOR );
        if ( option ) {
            event.preventDefault();
            chooseOption( option );
        }
    } );

    document.addEventListener( 'keydown', function ( event ) {
        const option = event.target.closest?.( OPTION_SELECTOR );
        if ( ! option || ! [ 'ArrowLeft', 'ArrowUp', 'ArrowRight', 'ArrowDown' ].includes( event.key ) ) {
            return;
        }
        const group = option.closest( '[data-uc-attribute]' );
        if ( ! group ) {
            return;
        }
        const options = Array.from( group.querySelectorAll( OPTION_SELECTOR ) ).filter( function ( candidate ) {
            return ! candidate.disabled;
        } );
        const current = options.indexOf( option );
        if ( current < 0 || options.length < 2 ) {
            return;
        }
        event.preventDefault();
        const direction = [ 'ArrowRight', 'ArrowDown' ].includes( event.key ) ? 1 : -1;
        const next = options[ ( current + direction + options.length ) % options.length ];
        next.focus();
        chooseOption( next );
    } );

    document.addEventListener( 'DOMContentLoaded', function () {
        document.querySelectorAll( FORM_SELECTOR ).forEach( function ( form ) {
            updateUi( form, '' );
        } );
    } );
}() );
