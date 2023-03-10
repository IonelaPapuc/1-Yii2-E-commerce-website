<?php

namespace frontend\controllers;

use common\models\CartItem;
use common\models\Order;
use common\models\OrderAddress;
use common\models\Product;

use common\models\User;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\AuthorizationsGetRequest;
use Sample\PayPalClient;
use Yii;
use yii\filters\ContentNegotiator;

use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CartController extends \frontend\base\Controller
{
    public function behaviors()
    {
        return [
            ['class' => ContentNegotiator::class,
                'only' => ['add', 'create-order', 'submit-payment', 'change-quantity'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,


                ],
            ],
            [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST', 'DELETE'],
                    'create-order' => ['POST']
                ]
            ]
        ];
    }

    public function actionIndex()
    {

        $cartItems = CartItem::getItemsForUser(currUserId());


        return $this->render('index', [
            'items' => $cartItems
        ]);
    }

    public function actionAdd()
    {
        $id = \Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("product does not exist");
        }

        if (\Yii::$app->user->isGuest) {


            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            $found = false;
            foreach ($cartItems as &$item) {
                if ($item['id'] == $id) {
                    $item['quantity']++;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $cartItem = [
                    'id' => $id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'price' => $product->price,
                    'quantity' => 1,
                    'total_price' => $product->price
                ];
                $cartItems[] = $cartItem;
            }


            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {


            $userId = \Yii::$app->user->id;
            $cartItem = CartItem::find()->userId($userId)->productId($id)->one();

            if ($cartItem) {
                $cartItem->quantity++;

            } else {


                $cartItem = new CartItem();
                $cartItem->product_id = $id;
                $cartItem->created_by = \Yii::$app->user->id;
                $cartItem->quantity = 1;
            }
            if ($cartItem->save()) {
                return [
                    'success' => true
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => $cartItem->errors];
            }
        }
    }

    public function actionDelete($id)
    {
        if (isGuest()) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as $i => $cartItem) {
                if ($cartItem['id'] == $id) {
                    array_splice($cartItems, $i, 1);
                    break;
                }
            }
            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            CartItem::deleteAll(['product_id' => $id, 'created_by' => currUserId()]);
        }
        return $this->redirect(['index']);
    }

    public function actionChangeQuantity()
    {

        $id = \Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("product does not exist");
        }
        $quantity = \Yii::$app->request->post('quantity');
        if (isGuest()) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as &$cartItem) {
                if ($cartItem['id'] === $id) {
                    $cartItem['quantity'] = $quantity;
                    break;
                }
            }
            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            $cartItem = CartItem::find()->userId(currUserId())->productId($id)->one();
            if ($cartItem) {
                $cartItem->quantity = $quantity;
                $cartItem->save();
            }
        }

        return[ 'quantity' => CartItem::getTotalQuantityForUser(currUserId()),
            'price' => Yii::$app->formatter->asCurrency( CartItem::getTotalPriceForItemForUser($id, currUserId()))
];
    }

    public function actionCheckout()
    {
        $cartItems = CartItem::getItemsForUser(currUserId());
        $productQuantity = CartItem::getTotalQuantityForUser(currUserId());
        $totalPrice = CartItem::getTotalPriceForUser(currUserId());

        if(empty($cartItems)){
            return $this->redirect([Yii::$app->homeUrl]);
        }
        $order = new Order();

        $order->total_price = $totalPrice;
        $order->status = Order::STATUS_DRAFT;
        $order->created_at = time();
        $order->created_by = currUserId();
        $transaction = Yii::$app->db->beginTransaction();
        if ($order->load(Yii::$app->request->post()) && $order->save() && $order->saveAddress(Yii::$app->request->post()) && $order->saveOrderItems()) {
            $transaction->commit();

          CartItem::clearCartItems(currUserId());

            //  return [
            //    'success' => true
            //  ];

            return $this->render('pay-now', [
                'order' => $order,
                //'orderAddress' => $order->orderAddress

            ]);
        }

        $orderAddress = new OrderAddress();
        if (!isGuest()) {
            /**
             * @var User $user
             */
            $user = \Yii::$app->user->identity;
            $userAddress = $user->getAddress();

            $order->firstname = $user->firstname;
            $order->lastname = $user->lastname;
            $order->email = $user->email;
            $order->status = Order::STATUS_DRAFT;


            $orderAddress->address = $userAddress->address;
            $orderAddress->city = $userAddress->city;
            $orderAddress->state = $userAddress->state;
            $orderAddress->country = $userAddress->country;
            $orderAddress->zipcode = $userAddress->zipcode;

        }


        return $this->render('checkout', [
            'order' => $order,
            'orderAddress' => $orderAddress,
            'cartItems' => $cartItems,
            'productQuantity' => $productQuantity,
            'totalPrice' => $totalPrice
        ]);
    }



 public function actionSubmitPayment($orderId)
 {
     $where = ['id' => $orderId, 'status' => Order::STATUS_DRAFT];
     if (!isGuest()) {
         $where['created_by'] = currUserId();
     }
     $order = Order::findOne($where);
     if (!$order) {
         throw new NotFoundHttpException();
     }
///cond transactie client payal
     $req =Yii::$app->request;
     $paypalOrderId= $req->post('orderId');
///terminat


     $order->transaction_id = $req->post('tansactionId');
     $exists = Order::find()->andWhere(['paypal_order_id' => $paypalOrderId])->exists();
     if ($exists) {
         throw new BadRequestHttpException();
     }
    $environment = new SandboxEnvironment(Yii::$app->params['paypalClientId'], Yii::$app->params['paypalSecret']);
    $client = new PaypalHttpClient($environment);
    // $response = $client->execute(new AuthorizationsGetRequest($order->transaction_id));




//cod transactie client payal


     $response = $client->execute(new OrdersGetRequest($paypalOrderId));
        if($response->statusCode === 200)
        {
    $order->paypal_order_id = $paypalOrderId;
  //  $order->status = $response->result-> status === 'COMPLETED' ? Order::STATUS_COMPLETED : ORDER::STATUS_FAILURED;
    $paidAmount = 0;
    foreach ($response->result->purchase_unit as $purchase_unit)

    {
        if($purchase_unit->amount->currency_code === 'USD')
        {
            $paidAmount += $purchase_unit->amount->value;
        }
    }
            if ($paidAmount === $order->total_price && $response->result->status === 'COMPLETED')
            {
                $order->status = Order::STATUS_COMPLETED;
            }
            $order->transaction_id= $response->result->purchase_units[0]->payments->captures[0]->id;
            if ($order->save())
            { if(!$order->sendEmailToVendor()){
                Yii::error("Email to the vendor is not sent");
            }
            if(!$order->sendEmailToCustomar()){
                Yii::error("Email to the customer is not sent");
            }

                return[
                    'success'=> true
                ];
            } else {
                Yii::error("Order was not saved. Data:" .VarDumper::dumpAsString($order->toArray()) . ' Errors ' . VarDumper::dumpAsString($order->errors));

            }
         }
throw new BadRequestHttpException();



   //  print "status Code : {$response->statusCode}\n";
   //  print "Status: {$response->result->status}\n";
  //   print "Order ID: {$response->result->id}\n";
   //  print "Intent: {$response->result->intent}\n";
  //   print "Links:\n";
  //   foreach ($response->result->links as $link) {
        // print "\t{$link->rel}: {$link->href}\tCall Type: {$link->method}\n";
    // }
  //   print "Gross Amount: {$response->result->purchase_units[0]->amount->currency_code} {$response->result->purchase_units[0]->amount->value}\n";
  //   echo json_encode($response->result, JSON_PRETTY_PRINT), "\n";

//terminat





    }
}



