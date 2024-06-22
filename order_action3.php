<?php
    header('Content-Type: application/json');
    include('../inc/DB.class.php');
    include('../inc/functions.php');
    include('../constant.php');

    $store_name = !empty($_REQUEST['shop']) ? $_REQUEST['shop'] : '';
    $order_id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : '';
    $order_action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $db = new DB();
    $table = 'stores_gyata';
    $conditions['return_type'] = 'single';
    $conditions['where'] = array('shop_url' => $store_name);
    $get_single_result = $db->getRows($table, $conditions);
    $access_token = $get_single_result['access_token'];

    $shop = $_GET['shop'];
    $token = $access_token;
    $name = $_GET['name'];

    $orderResponse = [
        'message' => '',
        'data' => [] 
    ];

    if (empty($store_name)) {
        $orderResponse['message'] = 'Store name is empty.';
    } else {
        $store_url = 'https://' . $get_single_result['shop_url'];
    
        // Check if access token is provided
        if (empty($access_token)) {
            $orderResponse['message'] = 'Access token is empty';
        } else {
            // Check if order id is provided
            if (empty($order_id)) {
                $orderResponse['message'] = 'Order id is required';
            } else {
                // Check if order action is provided
                if (empty($order_action)) {
                    $orderResponse['message'] = 'Order action is not defined';
                } else {
                    // Construct the store URL based on the order action
                    switch ($order_action) {
                        case 'cancel':
                            $store_url .= '/admin/api/' . API_VERSION . '/orders/' . $order_id . '/cancel.json';
                            break;
                        case 'close':
                            $store_url .= '/admin/api/' . API_VERSION . '/orders/' . $order_id . '/close.json';
                            break;
                        case 'open':
                            $store_url .= '/admin/api/' . API_VERSION . '/orders/' . $order_id . '/open.json';
                            break;
                        default:
                            $orderResponse['message'] = 'Invalid order action';
                    }
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                    CURLOPT_URL => $store_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'X-Shopify-Access-Token: '.$access_token,
                        'Content-Type: application/json',
                    ),
                    ));
                    $response = curl_exec($curl);
                    curl_close($curl);
                    $orderArr = json_decode($response,true);
                    if(!empty($orderArr) && empty($orderArr['errors'])){
                        switch ($order_action) {
                            case 'cancel':
                                $orderResponse['message'] = 'Order canceled sucessfully';
                                break;
                            case 'close':
                                $orderResponse['message'] = 'Order closed sucessfully';
                                break;
                            case 'open':
                                $orderResponse['message'] = 'Order re-opened sucessfully';
                                break;
                            default:
                                $orderResponse['message'] = 'Invalid order action';
                        }
                        $orderResponse['data'] = $orderArr;
                    } else {
                        $orderResponse['message'] = 'Order not found';    
                    }
                }
            }
        }
    }
    $orderJson = json_encode($orderResponse);
    echo $orderJson;
    die();
?>