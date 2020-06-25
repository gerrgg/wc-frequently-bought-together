const FrequentlyPurchasedTogether = ( e ) => {
	
	let button = e.querySelector('#wcfpt-add-to-cart-button');
	let inputs = e.getElementsByTagName('input');

	const setup = () => {
		/**
		 * initialize Event Listeners and add to cart button url 
		 */
		
		for( let input of inputs ){
			input.addEventListener( 'click', addToOrderList )
		}
	
		addToOrderList();
	}

	const addToOrderList = ( ) => {
		/**
		 * Creates a list of product ids based on which inputs are checked off
		 */
		let orderList = [];

		for( let input of inputs ){
			if( input.checked ) orderList.push(input.value)
		}

		buildUrl( orderList )

	};

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

let wcftp = document.getElementById('wcfpt-frequently-purchased-together');

// Run only when form available.
if( document.body.contains( wcftp ) ){
	FrequentlyPurchasedTogether( wcftp );
}





