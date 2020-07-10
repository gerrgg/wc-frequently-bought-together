const FrequentlyPurchasedTogether = ( e ) => {
	
	let button = e.querySelector('#wcfbt-add-to-cart-button');
	let inputs = e.getElementsByTagName('input');
	let selects = e.getElementsByTagName('select')
	let priceTotalDom = e.querySelector('#wcfbt-price-total');

	const setup = () => {
		/**
		 * initialize Event Listeners and add to cart button url 
		 */
		
		for( let input of inputs ){
			input.addEventListener( 'click', countInputs )
		}

		for( let select of selects ){
			select.addEventListener( 'change', getVariationIdFromAttributes )

			if( select.value !== '' ) getVariationIdFromAttributes( select )

		}
		
		countInputs();
	}

	const getVariationIdFromAttributes = ( e ) => {

		// Get the right target dom element
		let select = ( e.target === undefined ) ? e : e.target;

		if( select !== undefined ){

			// get all related variation selects
			let variation_dropdowns = document.getElementsByClassName( select.className )

			// get variable product id
			let parentId = select.className.replace( 'wcfbt_', '' )

			// checkbox whose value is the ID being added to the cart
			let parentCheckbox = select.parentElement.previousElementSibling;
			
			let selected_options = {};
			let ready = true;

			// check if all dropdowns are set and package data if so
			for( let dropdown of variation_dropdowns ){

				if( dropdown.value === '' ){
					ready = false;
				} else {
					selected_options[dropdown.name] = dropdown.value
				}
				
			}

			// get variation_id using selected attributes
			if( ready ){

				let data = { 
					action: 'wcfbt_get_variation_id', 
					options: selected_options,
					parent_id: parentId
				}

				jQuery.post( wp_ajax.url, data, function( response ) {
					console.log( response )

					// set checkbox to variation_id
					parentCheckbox.value = response[0];

					// set item price to variation price_html
					if( response[1].length > 0 )
						parentCheckbox.nextElementSibling.lastElementChild.innerHTML = response[1]

					// recount inputs and rebuild add-to-cart URL
					countInputs();
				} );
			}

		}

	}

	const countInputs = ( ) => {
		/**
		 * Creates a list of product ids based on which inputs are checked off
		 */
		let orderList = [];
		let totalPrice = 0;

		for( let input of inputs ){

			if( input.checked ) {
				orderList.push(input.value)

				// Get price element and remove '$'
				totalPrice += parseFloat(input.nextElementSibling.lastElementChild.innerText.substring(1));
			}
			
		}
		
		setTotalPrice( totalPrice )
		buildUrl( orderList )

	};

	const setTotalPrice = ( totalPrice ) => {
		priceTotalDom.innerText = '$' + totalPrice.toFixed(2);
	}

	const buildUrl = ( orderList ) => {
		/**
		 * Edit the Add to Cart URL to reflect the number of items checked off in the form
		 * @param array - List of products ids
		 */

		let orderString = '';

		orderList.forEach(id => {
			orderString += id + ','
		});
		
		let url = window.location.protocol + '//' + window.location.hostname + '/cart/?add-to-cart=' + orderString;

		button.href = url;

		editButtonText( orderList.length, button )

	}

	const editButtonText = ( length, button ) => {
		/**
		 * Edit the Add To Cart button text to reflect the number of items being added.
		 * @param int - number of items being added
		 * @param element - Add to cart button DOM
		 */

		let buttonText = 'Add ';

		if( length == 2 ){ buttonText += 'both ' }
		else if( length == 3 ){ buttonText += 'all three ' }
		else if( length > 3 ){ buttonText += 'all ' }
		else{ buttonText += '' }

		buttonText += 'to cart'

		button.innerHTML = buttonText
	}

	// init
	setup();

};

let wcftp = document.getElementById('wcfbt-frequently-bought-together');

// Run only when form available.
if( document.body.contains( wcftp ) ){
	FrequentlyPurchasedTogether( wcftp );
}





