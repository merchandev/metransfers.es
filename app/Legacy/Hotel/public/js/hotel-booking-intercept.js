jQuery(document).ready(function($) {
    if ( typeof hqp_vars === 'undefined' ) return;

    // 1. Visual Feedback
    const discount = parseFloat(hqp_vars.discount_percent);
    
    // Observer to watch for price updates in the booking form
    const targetNode = document.getElementById('wptb-step-2'); // or specific price element container
    if (targetNode) {
        const observer = new MutationObserver(function(mutationsList) {
            for(let mutation of mutationsList) {
                if (mutation.type === 'attributes' && $(mutation.target).is(':visible')) {
                    updatePriceDisplay();
                }
            }
        });
        observer.observe(document.getElementById('wptb-booking-app'), { attributes: true, subtree: true, childList: true });
        
        // Also direct check on step transition
        // The original plugin does $('#wptb-step-2').fadeIn(300);
        
        // We can hook into the button click that triggers calculation?
        $('#wptb-search-form').on('submit', function() {
            // Wait for calculation (it's async google maps)
            // We can't easily wait for the other plugin's callback.
            // Using MutationObserver on the #summary-price text change is best.
        });

        const priceObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.id === 'summary-price') {
                    // Check if we already appended
                    if ($(mutation.target).text().indexOf('OFF') === -1) {
                        applyDiscountVisuals();
                    }
                }
            });
        });
        
        const priceEl = document.getElementById('summary-price');
        if(priceEl) {
             priceObserver.observe(priceEl, { childList: true, characterData: true, subtree: true });
        }
    }

    function applyDiscountVisuals() {
        const $priceEl = $('#summary-price');
        const text = $priceEl.text(); // e.g. "€100.00"
        
        // Extract number
        const priceVal = parseFloat(text.replace(/[^\d.]/g, ''));
        
        if (!isNaN(priceVal) && priceVal > 0) {
            // Apply Discount Logic
            const discounted = (priceVal * (1 - discount/100)).toFixed(2);
            
            // 1. Update UI
            $priceEl.html(
                '<span style="text-decoration: line-through; color: #999; font-size: 0.8em;">€' + priceVal + '</span> ' + 
                '<span style="color: #27ae60; font-weight: bold;">€' + discounted + '</span> ' +
                '<span class="badge" style="background: #27ae60; color: white; padding: 2px 5px; border-radius: 3px; font-size: 0.6em; vertical-align: middle;">' + discount + '% OFF</span>'
            );
            
            // 2. Add Notice
            if ($('#hqp-discount-notice').length === 0) {
                $('#wptb-step-2').find('h3').after('<div id="hqp-discount-notice" style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px; text-align:center;">🎉 ' + hqp_vars.message + '</div>');
            }

            // 3. PERSIST TO SESSION STORAGE (Critical for Payment)
            const savedData = sessionStorage.getItem('wptb_booking_data');
            if (savedData) {
                try {
                    let data = JSON.parse(savedData);
                    
                    // Only update if not already discounted or if price changed
                    if (parseFloat(data.price) !== parseFloat(discounted)) {
                        console.log('HQP: Updating Price in SessionStorage', data.price, '->', discounted);
                        
                        data.original_price = priceVal;
                        data.discount_percent = discount;
                        data.price = discounted; // This is what Stripe will see/charge (visually at least)
                        
                        sessionStorage.setItem('wptb_booking_data', JSON.stringify(data));
                    }
                } catch(e) { console.error('HQP error updating session', e); }
            }
        }
    }

});
