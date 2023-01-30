<?php


/** @var \common\models\Order $order */
/**  @var \common\models\OrderAddress $orderAddress */
/** @var array $cartItems */
/** @var int $productQuantity */
/** @var float $totalPrice */

$orderAddress = $order->orderAddress
?>
<script src="https://www.paypal.com/sdk/js?client-id=test&currency=USD"></script>

<h3>Order #<?php echo $order->id ?> summary</h3>

<div class="row">
    <div class="col">
        <h4> Account information</h4>
        <table class="table">
            <tr>
                <th>Firstname</th>
                <td ><?php echo $order->firstname ?></td>
            </tr>
            <tr>
                <th>Lastname</th>
                <td ><?php echo $order->lastname ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo $order->email ?></td>
            </tr>

        </table>
        <h4> Address information</h4>
        <table class="table">
            <tr>
            <th>Address</th>
            <td><?php echo $orderAddress->address ?></td>
            </tr>
            <tr>
            <th>City</th>
            <td><?php echo $orderAddress->city ?></td>
            </tr>
            <tr>
            <th>State</th>
            <td><?php echo $orderAddress->state ?></td>
            </tr>
            <tr>
            <th>Country</th>
            <td><?php echo $orderAddress->country ?></td>
            </tr>
            <tr>
            <th>Zipcode</th>
            <td><?php echo $orderAddress->zipcode ?></td>
            </tr>

        </table>
    </div>

    <div class="col">
        <h5>Products</h5>
        <table class="table table-sm">
            <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($order->orderItems as $item): ?>
        <tr>
            <td>
                <img src="<?php echo $item->product->getImageUrl() ?>" style="width: 70px;">
            </td>
            <td><?php echo $item->product_name ?> </td>
            <td><?php echo $item->quantity ?></td>
            <td><?php echo Yii::$app->formatter->asCurrency($item->quantity * $item->unit_price) ?></td>

        </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
        <hr>
       <table class="table">
       <tr>
           <th>Total Items</th>
           <td><?php echo $order->getItemsQuantity() ?></td>
       </tr>
           <tr>
           <th>Total Price</th>
           <td><?php echo Yii::$app->formatter->asCurrency($order->total_price) ?></td>
           </tr>
       </table>

        <div id="paypal-button-container"></div>

    </div>
</div>

<script>

    paypal.Buttons({

        // Set up the transaction
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '0.01'
                    }
                }]
            });
        },

        // Finalize the transaction
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(orderData) {
                // Successful capture! For demo purposes:
                console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
                var transaction = orderData.purchase_units[0].payments.captures[0];
                alert("Thanks for your bisness");
                window.location.href= '';


                // Replace the above to show a success message within this page, e.g.
                // const element = document.getElementById('paypal-button-container');
                // element.innerHTML = '';
                // element.innerHTML = '<h3>Thank you for your payment!</h3>';
                // Or go to another URL:  actions.redirect('thank_you.html');
            });
        }


    }).render('#paypal-button-container');
</script>