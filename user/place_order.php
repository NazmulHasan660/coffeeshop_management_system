<?php
include '../config/db.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Place Order - Coffee Shop</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,700&family=Roboto&display=swap" rel="stylesheet" />

<!-- Bootstrap CSS -->
<link href="../css/bootstrap.min.css" rel="stylesheet" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
/* Existing styling, plus radio and table styling */

/* ... your existing CSS unchanged, keep all from your original code ... */

body {
    background-color: #6F4E37;
    color: #f4eee8;
    font-family: 'Roboto', sans-serif;
    height: 100vh;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}
.order-container {
    background-color: #f4eee8;
    color: #4b3621;
    border-radius: 20px;
    padding: 40px 50px;
    max-width: 900px;
    width: 100%;
    box-shadow: 0 10px 30px rgba(111, 78, 55, 0.7);
    font-size: 1rem;
}
h1 {
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-weight: 700;
    font-size: 3rem;
    color: #4b3621;
    margin-bottom: 30px;
    text-align: center;
    letter-spacing: 2px;
}
p.description {
    font-style: italic;
    font-size: 1.1rem;
    text-align: center;
    margin-bottom: 35px;
    color: #7a5a44;
}
table {
    width: 100%;
}
thead {
    background-color: #6F4E37;
    color: #f4eee8;
}
thead th {
    padding: 14px 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    white-space: nowrap;
}
tbody tr:hover {
    background-color: #ecdac8;
    cursor: pointer;
    transition: background-color 0.3s ease;
    color: #6F4E37;
}
tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    font-size: 1rem;
}
tbody td em {
    font-style: italic;
}
input[type=number] {
    border-radius: 12px;
    border: 2px solid #c9b89c;
    padding: 6px 10px;
    font-size: 1rem;
    color: #4b3621;
    transition: border-color 0.3s ease;
    width: 70px;
}
input[type=number]:focus {
    outline: none;
    border-color: #6F4E37;
    box-shadow: 0 0 8px #a67c52;
}
.btn-coffee {
    display: block;
    background-color: #6F4E37;
    color: #f4eee8;
    border: none;
    border-radius: 30px;
    padding: 14px 40px;
    font-weight: 700;
    font-size: 1.25rem;
    margin: 30px auto 0;
    transition: background-color 0.3s ease;
    cursor: pointer;
    box-shadow: 0 5px 16px rgba(111, 78, 55, 0.7);
}
.btn-coffee:hover, .btn-coffee:focus {
    background-color: #563B2A;
    outline: none;
    box-shadow: 0 6px 20px rgba(86, 59, 42, 0.8);
}
#no-items-msg {
    display: none;
    text-align: center;
    font-style: italic;
    color: #7a5a44;
    font-size: 1.2rem;
    margin-top: 60px;
}

/* Styled radio button groups */
.radio-group {
    display: flex;
    gap: 8px;
    user-select: none;
}
.radio-group label {
    position: relative;
    padding: 8px 16px;
    border: 2px solid #c9b89c;
    border-radius: 24px;
    font-size: 0.95rem;
    color: #4b3621;
    cursor: pointer;
    font-family: 'Roboto', sans-serif;
    transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}
.radio-group input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.radio-group input[type="radio"]:checked + label,
.radio-group label:hover {
    background-color: #6F4E37;
    border-color: #6F4E37;
    color: #f4eee8;
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.5);
}
.radio-group label {
    padding-left: 18px;
}
.radio-group input[type="radio"]:focus + label {
    outline: 3px solid #a67c52;
    outline-offset: 2px;
}

/* Responsive styling */
@media (max-width: 768px) {
    .order-container {
        padding: 30px 25px;
    }
    h1 {
        font-size: 2.5rem;
    }
    input[type=number] {
        width: 60px;
    }
}

</style>

<script>
$(document).ready(function() {
    // Render temperature selection radio buttons
    function renderTemperatureRadio(id, selectedValue) {
        return `
        <div class="radio-group" role="radiogroup" aria-label="Temperature choice">
            <input type="radio" id="temp_hot_${id}" name="temperature[${id}]" value="Hot" ${selectedValue === 'Hot' ? 'checked' : ''} />
            <label for="temp_hot_${id}">Hot</label>

            <input type="radio" id="temp_cold_${id}" name="temperature[${id}]" value="Cold" ${selectedValue === 'Cold' ? 'checked' : ''} />
            <label for="temp_cold_${id}">Cold</label>
        </div>`;
    }

    // Render size selection radio buttons
    function renderSizeRadio(id, selectedValue) {
        return `
        <div class="radio-group size-group" role="radiogroup" aria-label="Size choice">
            <input type="radio" id="size_small_${id}" name="size[${id}]" value="Small" ${selectedValue === 'Small' ? 'checked' : ''} />
            <label for="size_small_${id}">Small</label>

            <input type="radio" id="size_medium_${id}" name="size[${id}]" value="Medium" ${selectedValue === 'Medium' ? 'checked' : ''} />
            <label for="size_medium_${id}">Medium</label>

            <input type="radio" id="size_large_${id}" name="size[${id}]" value="Large" ${selectedValue === 'Large' ? 'checked' : ''} />
            <label for="size_large_${id}">Large</label>
        </div>`;
    }

    // Escape HTML to avoid XSS
    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }

    // Save current selections on the form so we can restore after refresh
    function saveCurrentSelections() {
        let selectionData = {};

        $('#menu-items-tbody tr').each(function() {
            let row = $(this);
            let id = row.find('input[type=number]').attr('name').match(/\d+/)[0]; // extract id from quantity input name='quantity[id]'
            let quantity = row.find('input[type=number]').val();

            // Find selected temperature radio
            let tempSelected = row.find(`input[name="temperature[${id}]"]:checked`).val();
            // Find selected size radio
            let sizeSelected = row.find(`input[name="size[${id}]"]:checked`).val();

            selectionData[id] = {
                quantity: quantity,
                temperature: tempSelected || 'Hot',  // default to Hot if none
                size: sizeSelected || 'Medium'       // default to Medium if none
            };
        });

        return selectionData;
    }

    // Restore selections after refresh
    function restoreSelections(selections) {
        $('#menu-items-tbody tr').each(function() {
            let row = $(this);
            let id = row.find('input[type=number]').attr('name').match(/\d+/)[0];
            let data = selections[id];
            if (data) {
                row.find('input[type=number]').val(data.quantity);

                // Set temperature radios
                row.find(`input[name="temperature[${id}]"][value="${data.temperature}"]`).prop('checked', true);

                // Set size radios
                row.find(`input[name="size[${id}]"][value="${data.size}"]`).prop('checked', true);
            }
        });
    }

    // Fetch menu items from server and render table body
    function fetchMenuItems() {
        // Save user selections first
        let previousSelections = saveCurrentSelections();

        $.ajax({
            url: '../auth/menu_fetch.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                let tbody = $('#menu-items-tbody');
                
                if (data.length === 0) {
                    $('#no-items-msg').show();
                    $('#orderForm').hide();
                    tbody.empty();
                } else {
                    $('#no-items-msg').hide();
                    $('#orderForm').show();

                    tbody.empty();

                    $.each(data, function(i, item) {
                        // Use saved values or defaults
                        let prev = previousSelections[item.id] || {};
                        let quantity = (prev.quantity !== undefined) ? prev.quantity : '0';
                        let tempSelected = prev.temperature || 'Hot';
                        let sizeSelected = prev.size || 'Medium';

                        tbody.append(`
                            <tr>
                                <td>${escapeHtml(item.name)}</td>
                                <td><em>${escapeHtml(item.description)}</em></td>
                                <td>${parseFloat(item.price).toFixed(2)}</td>
                                <td>${renderTemperatureRadio(item.id, tempSelected)}</td>
                                <td>${renderSizeRadio(item.id, sizeSelected)}</td>
                                <td><input type="number" name="quantity[${item.id}]" min="0" max="99" value="${quantity}" aria-label="Quantity for ${escapeHtml(item.name)}" /></td>
                            </tr>
                        `);
                    });
                }
            },
            error: function() {
                alert('Failed to fetch menu items.');
            }
        });
    }

    // Initial fetch and then refresh every 10 seconds
    fetchMenuItems();
    setInterval(fetchMenuItems, 10000);

});
</script>

</head>
<body>

<div class="order-container">
    <h1>Place Your Order</h1>
    <p class="description">Choose your favorite coffee items, size, temperature, and quantities below.</p>

    <p id="no-items-msg">No menu items available for order at this time.</p>

    <form action="billing.php" method="post" id="orderForm" novalidate>
        <table class="table table-striped table-bordered" aria-label="Menu items table for placing order">
            <thead>
                <tr>
                    <th>Menu Item</th>
                    <th>Description</th>
                    <th>Price ($)</th>
                    <th>Temperature</th>
                    <th>Size</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody id="menu-items-tbody">
                <!-- AJAX dynamically loads content here -->
            </tbody>
        </table>

        <button type="submit" class="btn-coffee">Proceed to Billing</button>
    </form>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
