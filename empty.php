<script>
let orders = [];  // Store orders data

function fetchOrders() {
    fetch('check_orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                orders = data.orders;
                displayOrders(); // Display all orders when fetched
            } else {
                console.error('Error fetching orders:', data.message);
            }
        })
        .catch(error => console.error('Error fetching orders:', error));
}

function displayOrders() {
    const ordersList = document.getElementById('orders-list');
    ordersList.innerHTML = '';  // Clear previous orders list

    orders.forEach(order => {
        const orderElement = document.createElement('div');
        orderElement.classList.add('order-item');
        orderElement.innerHTML = `
            <p>Order #${order.order_id}</p>
            <p>Status: <span id="status-${order.order_id}">${order.status_name}</span></p>
            <p>Amount: ${order.amount}</p>
        `;
        ordersList.appendChild(orderElement);
    });
}

function updateLatestOrderStatus() {
    fetch('check_orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const latestOrder = data.orders[0];  // The latest order should be the first
                const latestOrderElement = document.getElementById(`status-${latestOrder.order_id}`);

                // Update only if the status has changed
                if (latestOrderElement && latestOrderElement.textContent !== latestOrder.status_name) {
                    latestOrderElement.textContent = latestOrder.status_name;
                    console.log('Updated latest order status:', latestOrder.status_name);
                }
            }
        })
        .catch(error => console.error('Error updating order status:', error));
}

// Poll for updates every 5 seconds
setInterval(updateLatestOrderStatus, 5000);

// Fetch the orders when the page loads
fetchOrders();
</script>