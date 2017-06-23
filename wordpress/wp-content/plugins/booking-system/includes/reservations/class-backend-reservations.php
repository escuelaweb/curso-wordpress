<?php

/*
* Title                   : Pinpoint Booking System WordPress Plugin
* Version                 : 2.1.1
* File                    : includes/reservations/class-backend-reservations.php
* File Version            : 1.0.5
* Created / Last Modified : 26 August 2015
* Author                  : Dot on Paper
* Copyright               : © 2012 Dot on Paper
* Website                 : http://www.dotonpaper.net
* Description             : Back end reservations PHP class.
*/

    if (!class_exists('DOPBSPBackEndReservations')){
        class DOPBSPBackEndReservations extends DOPBSPBackEnd{
            /*
             * Constructor.
             */
            function __construct(){
            }

            /*
             * Prints out the reservations page.
             * 
             * @return HTML page
             */        
            function view(){
                global $DOPBSP;
                
                /*
                 * Check if reservations have expired each time you open the reservations page.
                 */
                $this->clean();
                
                /*
                 * Display reservations template.
                 */
                $DOPBSP->views->backend_reservations->template();
            }
            
            /*
             * Search & display reservations list.
             * 
             * 
             * @post type (string): type ( csv/json )
             * @post key (string): API key
             * @post calendar_id (string/integer): list of calendars or calendar
             * @post start_date (string): reservations start date
             * @post end_date (string): reservations end date
             * @post start_hour (string): reservations start hour
             * @post end_hour (string): reservations end hour
             * @post status_pending (boolean): display reservations with status pending
             * @post status_approved (boolean): display reservations with status approved
             * @post status_rejected (boolean): display reservations with status rejected
             * @post status_canceled (boolean): display reservations with status canceled
             * @post status_expired (boolean): display reservations with status expired
             * @post payment_methods (string): list of payment methods
             * @post search (string): search text
             * @post page (integer): page number to be displayed
             * @post per_page (integer): number of reservation displayed per page
             * @post order (string): order direction "ASC", "DESC"
             * @post order_by (string): order by "check_in", "check_out", "start_hour", "end_hour", "id", "status", "date_created"
             * 
             * @get dopbsp_api (boolean): will initilize API calls if it is enabled
             * @get calendar_id (string/integer): list of calendars or calendar
             * @get start_date (string): reservations start date
             * @get end_date (string): reservations end date
             * @get start_hour (string): reservations start hour
             * @get end_hour (string): reservations end hour
             * @get status (boolean): display reservations with selected status
             * @get payment_methods (string): list of payment methods
             * @get search (string): search text
             * @get page (integer): page number to be displayed
             * @get per_page (integer): number of reservation displayed per page
             * @get order (string): order direction "ASC", "DESC"
             * @get order_by (string): order by "check_in", "check_out", "start_hour", "end_hour", "id", "status", "date_created"
             * 
             * @return reservations list
             */
            function get(){
                global $wpdb;
                global $DOPBSP;
                
                $calendars_ids = array();
                $query = array();
                $values = array();
                $api = isset($_GET['dopbsp_api']) && $_GET['dopbsp_api'] == 'true' ? true:false;
                
                if (!$api){
                    $type = $_POST['type'];
                    $calendar_id = $_POST['calendar_id'];
                    $start_date = $_POST['start_date'];
                    $end_date = $_POST['end_date'];
                    $start_hour = $_POST['start_hour'];
                    $end_hour = $_POST['end_hour'];
                    $status_pending = $_POST['status_pending'] == 'true' ? true:false;
                    $status_approved = $_POST['status_approved'] == 'true' ? true:false;
                    $status_rejected = $_POST['status_rejected'] == 'true' ? true:false;
                    $status_canceled = $_POST['status_canceled'] == 'true' ? true:false;
                    $status_expired = $_POST['status_expired'] == 'true' ? true:false;
                    $payment_methods = $_POST['payment_methods'] == '' ? array():explode(',', $_POST['payment_methods']);
                    $search = $_POST['search'];
                    $page = $_POST['page'];
                    $per_page = $_POST['per_page'];
                    $order = $_POST['order'];
                    $order_by = $_POST['order_by'];
                }
                else{
                    $type = isset($_GET['type']) ? $_GET['type']:'';
                    
                    if (isset($_GET['calendar_id'])
                            && $_GET['calendar_id'] != ''){
                        $calendars_requested = ','.$_GET['calendar_id'].',';
                    }
                    else{
                        $calendars_requested = '';
                    }
                    
                    if($type != 'ics') {
                        $calendars_id = array();
                        $key_pieces = explode('-', $_POST['key']);
                        $calendars = $DOPBSP->classes->backend_calendars->get(array('user_id' => (int)$key_pieces[1]));

                        foreach ($calendars as $calendar){
                            if ($calendars_requested != ''){
                                if (strpos($calendars_requested, ','.(string)$calendar->id.',') !== false){
                                    array_push($calendars_id, $calendar->id);
                                }
                            }
                            else{
                                array_push($calendars_id, $calendar->id);
                            }
                        }
                    
                        $calendar_id = implode(',', $calendars_id);
                    } else {
                        $calendar_id = $_GET['calendar_id'];
                    }
                    $start_date = isset($_GET['start_date']) ? $_GET['start_date']:'';
                    $end_date = isset($_GET['end_date']) ? $_GET['end_date']:'';
                    $start_hour = isset($_GET['start_hour']) ? $_GET['start_hour']:'00:00';
                    $end_hour = isset($_GET['end_hour']) ? $_GET['end_hour']:'24:00';
                    $status = isset($_GET['status']) ? $_GET['status']:'';
                    $status_pending = strpos($status,'pending') !== false ? true:false;
                    $status_approved = strpos($status,'approved') !== false ? true:false;
                    $status_rejected = strpos($status,'rejected') !== false ? true:false;
                    $status_canceled = strpos($status,'canceled') !== false ? true:false;
                    $status_expired = strpos($status,'expired') !== false ? true:false;
                    $payment_methods = isset($_GET['payment_methods']) && $_GET['payment_methods'] != '' ? explode(',', $_GET['payment_methods']):array();
                    $search = isset($_GET['search']) ? $_GET['search']:'';
                    $page = isset($_GET['page']) ? $_GET['page']:'1';
                    $per_page = isset($_GET['per_page']) ? $_GET['per_page']:'10';
                    $order = isset($_GET['order']) ? $_GET['order']:'ASC';
                    $order_by = isset($_GET['order_by']) ? $_GET['order_by']:'check_in';
                    
                    if(strtolower($type) == 'ics') {
                        $per_page = 1000000;
                    }
                }
                
                /*
                 * Calendars query.
                 */
                if (strpos($calendar_id, ',') !== false){
                    $calendars_ids = explode(',', $calendar_id);
                    array_push($query, 'SELECT * FROM '.$DOPBSP->tables->reservations.' WHERE (calendar_id=%d');
                    array_push($values, $calendars_ids[0]);
                    
                    for ($i=1; $i<count($calendars_ids); $i++){
                        array_push($query, ' OR calendar_id=%d');
                        array_push($values, $calendars_ids[$i]);
                    }
                    array_push($query, ')');
                }
                else{
                    array_push($query, 'SELECT * FROM '.$DOPBSP->tables->reservations.' WHERE calendar_id=%d');
                    array_push($values, $calendar_id);
                }

                /*
                 * Days query.
                 */
                if ($start_date != ''){
                    if ($end_date != ''){
                        array_push($query, ' AND (check_in >= %s AND check_in <= %s');
                        array_push($values, $start_date);
                        array_push($values, $end_date);
                    
                        array_push($query, ' OR check_out >= %s AND check_out <= %s AND check_out <> "")');
                        array_push($values, $start_date);
                        array_push($values, $end_date);
                    }
                    else{
                        array_push($query, ' AND (check_in >= %s)');
                        array_push($values, $start_date);
                    }
                }
                elseif ($end_date != ''){
                    array_push($query, ' AND check_in <= %s');
                    array_push($values, $end_date);
                }
               
                /*
                 * Hours query.
                 */
                array_push($query, ' AND (start_hour >= %s AND start_hour <= %s OR start_hour = ""');
                array_push($values, $start_hour);
                array_push($values, $end_hour);
                
                array_push($query, ' OR end_hour >= %s AND end_hour <= %s OR end_hour = "")');
                array_push($values, $start_hour);
                array_push($values, $end_hour);

                /*
                 * Status query.
                 */
                if ($status_pending 
                        || $status_approved 
                        || $status_rejected 
                        || $status_canceled 
                        || $status_expired){
                    $status_init = false;

                    if ($status_pending){
                        array_push($query, $status_init ? ' OR status = %s':' AND (status = %s');
                        array_push($values, 'pending');
                        $status_init = true;
                    }

                    if ($status_approved){
                        array_push($query, $status_init ? ' OR status = %s':' AND (status = %s');
                        array_push($values, 'approved');
                        $status_init = true;
                    }

                    if ($status_rejected){
                        array_push($query, $status_init ? ' OR status = %s':' AND (status = %s');
                        array_push($values, 'rejected');
                        $status_init = true;
                    }

                    if ($status_canceled){
                        array_push($query, $status_init ? ' OR status = %s':' AND (status = %s');
                        array_push($values, 'canceled');
                        $status_init = true;
                    }

                    if ($status_expired){
                        array_push($query, $status_init ? ' OR status = %s':' AND (status = %s');
                        array_push($values, 'expired');
                        $status_init = true;
                    }
                    array_push($query, ')');                    
                }
                else{
                    array_push($query, ' AND status <> %s');
                    array_push($values, 'expired');
                }

                /*
                 * Payment query.       
                 */
                if (count($payment_methods) > 0){
                    $payment_init = false;

                    for ($i=0; $i < count($payment_methods); $i++){
                        array_push($query, $payment_init ? ' OR payment_method=%s':' AND (payment_method=%s');
                        array_push($values, $payment_methods[$i]);
                        $payment_init = true;
                    }    
                    array_push($query, ')');                    
                }

                /*
                 * Search query.
                 */
                if ($search != ''){
                    array_push($query, ' AND (id=%s OR transaction_id=%s OR form LIKE %s)');
                    array_push($values, $search);
                    array_push($values, $search);
                    array_push($values, '%'.$search.'%');
                }
                
                /*
                 * Exclude reservations with incomplete payment.
                 */
                array_push($query, ' AND (token="" OR (token<>"" AND (payment_method="none" OR payment_method="default")))');
               
                /*
                 * Order query.
                 */
                $order_value = $order == 'DESC' ? 'DESC':'ASC';
                        
                switch ($order_by){
                    case 'check_out':
                        $order_by_value = 'check_out';
                        break;
                    case 'start_hour':
                        $order_by_value = 'start_hour';
                        break;
                    case 'end_hour':
                        $order_by_value = 'end_hour';
                        break;
                    case 'id':
                        $order_by_value = 'id';
                        break;
                    case 'status':
                        $order_by_value = 'status';
                        break;
                    case 'date_created':
                        $order_by_value = 'date_created';
                        break;
                    default:
                        $order_by_value = 'check_in';
                }
                
                array_push($query, ' ORDER BY '.$order_by_value.' '.($order_value));

                /*
                 * ************************************************************* Get number of reservations.
                 */
                if (!$api){
                    $reservations_total = $wpdb->get_var($wpdb->prepare(str_replace('*', 'COUNT(*)', implode('', $query)), $values));
                }
                else{
                    $reservations_total = 0;
                }

                /*
                 * Pagination query.
                 */
                array_push($query, ' LIMIT %d, %d');
                array_push($values, (($page-1)*$per_page));
                array_push($values, $per_page);
                
                /*
                 * ************************************************************* Get reservations.
                 */
                $reservations = $wpdb->get_results($wpdb->prepare(implode('', $query), $values));
                
                
                $csvReservations = array();
                $csvReservationHeader = array('ID', 'Calendar ID', 'Check In', 'Check Out', 'Start Hour');
                $excelReservations = array();
                $excelReservationsData = array();
                $jsonReservationsData = array();
                $icsReservationsData = array();
                
                
                // ICS
                if(strtolower($type) == 'ics') {
                    array_push($icsReservationsData, 'BEGIN:VCALENDAR');
                    array_push($icsReservationsData, 'PRODID:-//Pinpoint.world//Calendar 2.5.6//EN');
                    array_push($icsReservationsData, 'CALSCALE:GREGORIAN');
                    array_push($icsReservationsData, 'METHOD:PUBLISH');
                    array_push($icsReservationsData, 'VERSION:2.0');
                    
                    $timestamp = gmdate("Ymd\THis\Z");
                    
                    foreach($reservations as $reservation) {
                        $reservation->check_out = new DateTime($reservation->check_out);
                        $reservation->check_out = $reservation->check_out->modify('+1 day');
                        
                        // ICS
                        array_push($icsReservationsData, 'BEGIN:VEVENT');
                        array_push($icsReservationsData, 'UID:'.$timestamp.'-'.$reservation->email);
                        array_push($icsReservationsData, 'DTSTAMP:'.$timestamp);
                        array_push($icsReservationsData, 'DTEND;VALUE=DATE:'.str_replace('-','', $reservation->check_out->format('Ymd')));
                        array_push($icsReservationsData, 'DTSTART;VALUE=DATE:'.str_replace('-','', $reservation->check_in));
                        array_push($icsReservationsData, 'SUMMARY:Booked');
                        array_push($icsReservationsData, 'END:VEVENT');
                    }
                    
                    array_push($icsReservationsData, 'END:VCALENDAR');
                    
                    echo implode(PHP_EOL, $icsReservationsData);
                    exit;
                }

                foreach($reservations as $reservation) {
                    $csvReservation = array();
                    $reservations_form = json_decode($reservation->form);
                    $reservation = (array)$reservation;

                    array_push($excelReservationsData, '<tr>');
                    array_push($csvReservation, $reservation['id']);
                    array_push($excelReservationsData, '<td>'.$reservation['id'].'</td>');

                    if (!array_key_exists('id', $jsonReservationsData)) {
                        $jsonReservationsData['id'] = array();
                    }
                    array_push($jsonReservationsData['id'], $reservation['id']);

                    array_push($csvReservation, $reservation['calendar_id']);
                    array_push($excelReservationsData, '<td>'.$reservation['calendar_id'].'</td>');

                    if (!array_key_exists('calendar_id', $jsonReservationsData)) {
                        $jsonReservationsData['calendar_id'] = array();
                    }
                    array_push($jsonReservationsData['calendar_id'], $reservation['calendar_id']);

                    array_push($csvReservation, $reservation['check_in']);
                    array_push($excelReservationsData, '<td>'.$reservation['check_in'].'</td>');

                    if (!array_key_exists('check_in', $jsonReservationsData)) {
                        $jsonReservationsData['check_in'] = array();
                    }
                    array_push($jsonReservationsData['check_in'], $reservation['check_in']);



                    if($reservation['check_out'] == '') {
                        unset($csvReservationHeader[3]);
                    } else {
                        array_push($csvReservation, $reservation['check_out']);
                        array_push($excelReservationsData, '<td>'.$reservation['check_out'].'</td>');

                        if (!array_key_exists('check_out', $jsonReservationsData)) {
                            $jsonReservationsData['check_out'] = array();
                        }
                        array_push($jsonReservationsData['check_out'], $reservation['check_out']);
                    }

                    if($reservation['start_hour'] == '') {

                        if($reservation['check_out'] == '') {
                            unset($csvReservationHeader[3]);
                        } else {
                            unset($csvReservationHeader[4]);
                        }
                    } else {
                        array_push($csvReservation, $reservation['start_hour']);
                        array_push($excelReservationsData, '<td>'.$reservation['start_hour'].'</td>');

                        if (!array_key_exists('start_hour', $jsonReservationsData)) {
                            $jsonReservationsData['start_hour'] = array();
                        }
                        array_push($jsonReservationsData['start_hour'], $reservation['start_hour']);
                    }

                    if($reservation['end_hour'] != 0) {
                        array_push($csvReservation, $reservation['end_hour']);
                        array_push($excelReservationsData, '<td>'.$reservation['end_hour'].'</td>');

                        if (!array_key_exists('end_hour', $jsonReservationsData)) {
                            $jsonReservationsData['end_hour'] = array();
                            array_push($csvReservationHeader, 'End hour');
                        }
                        array_push($jsonReservationsData['end_hour'], $reservation['end_hour']);
                    }

                    if($reservation['price'] != 0) {
                        array_push($csvReservation, $reservation['price']);
                        array_push($excelReservationsData, '<td>'.$reservation['price'].'</td>');

                        if (!array_key_exists('price', $jsonReservationsData)) {
                            $jsonReservationsData['price'] = array();
                            array_push($csvReservationHeader, 'Price');
                        }
                        array_push($jsonReservationsData['price'], $reservation['price']);
                    }

                    if($reservation['extras_price'] != 0) {
                        array_push($csvReservation, $reservation['extras_price']);
                        array_push($excelReservationsData, '<td>'.$reservation['extras_price'].'</td>');

                        if (!array_key_exists('extras_price', $jsonReservationsData)) {
                            $jsonReservationsData['extras_price'] = array();
                            array_push($csvReservationHeader, 'Extras price');
                        }
                        array_push($jsonReservationsData['extras_price'], $reservation['extras_price']);
                    }

                    if($reservation['fees_price'] != 0) {
                        array_push($csvReservation, $reservation['fees_price']);
                        array_push($excelReservationsData, '<td>'.$reservation['fees_price'].'</td>');

                        if (!array_key_exists('fees_price', $jsonReservationsData)) {
                            $jsonReservationsData['fees_price'] = array();
                            array_push($csvReservationHeader, 'Fees price');
                        }
                        array_push($jsonReservationsData['fees_price'], $reservation['fees_price']);
                    }

                    if($reservation['coupon_price'] != 0) {
                        array_push($csvReservation, $reservation['coupon_price']);
                        array_push($excelReservationsData, '<td>'.$reservation['coupon_price'].'</td>');

                        if (!array_key_exists('coupon_price', $jsonReservationsData)) {
                            $jsonReservationsData['coupon_price'] = array();
                            array_push($csvReservationHeader, 'Coupon price');
                        }
                        array_push($jsonReservationsData['coupon_price'], $reservation['coupon_price']);
                    }

                    if($reservation['deposit_price'] != 0) {
                        array_push($csvReservation, $reservation['deposit_price']);
                        array_push($excelReservationsData, '<td>'.$reservation['deposit_price'].'</td>');

                        if (!array_key_exists('deposit_price', $jsonReservationsData)) {
                            $jsonReservationsData['deposit_price'] = array();
                            array_push($csvReservationHeader, 'Deposit price');
                        }
                        array_push($jsonReservationsData['deposit_price'], $reservation['deposit_price']);
                    }

                    if($reservation['fees_price'] != 0) {
                        array_push($csvReservation, $reservation['fees_price']);
                        array_push($excelReservationsData, '<td>'.$reservation['fees_price'].'</td>');

                        if (!array_key_exists('fees_price', $jsonReservationsData)) {
                            $jsonReservationsData['fees_price'] = array();
                            array_push($csvReservationHeader, 'Fees price');
                        }
                        array_push($jsonReservationsData['fees_price'], $reservation['fees_price']);
                    }
                    array_push($csvReservation, $reservation['price_total']);
                    array_push($excelReservationsData, '<td>'.$reservation['price_total'].'</td>');

                    if (!array_key_exists('price_total', $jsonReservationsData)) {
                        $jsonReservationsData['price_total'] = array();
                        array_push($csvReservationHeader, 'Total price');
                    }
                    array_push($jsonReservationsData['price_total'], $reservation['price_total']);
                    array_push($csvReservation, $reservation['currency_code']);
                    array_push($excelReservationsData, '<td>'.$reservation['currency_code'].'</td>');

                    if (!array_key_exists('currency_code', $jsonReservationsData)) {
                        $jsonReservationsData['currency_code'] = array();
                        array_push($csvReservationHeader, 'Currency');
                    }
                    array_push($jsonReservationsData['currency_code'], $reservation['currency_code']);

                    if($reservation['no_items'] != 0) {
                        array_push($csvReservation, $reservation['no_items']);
                        array_push($excelReservationsData, '<td>'.$reservation['no_items'].'</td>');

                        if (!array_key_exists('no_items', $jsonReservationsData)) {
                            $jsonReservationsData['no_items'] = array();
                            array_push($csvReservationHeader, 'No. Items');
                        }
                        array_push($jsonReservationsData['no_items'], $reservation['no_items']);
                    }
                    
                    // IP Address
                    if(isset($reservation['ip']) && $reservation['ip'] != '') {
                        
                        if (!array_key_exists('ip', $jsonReservationsData)) {
                            $jsonReservationsData['ip'] = array();
                            array_push($csvReservationHeader, 'IP address');
                        }
                        array_push($excelReservationsData, '<td>'.$reservation['ip'].'</td>');
                    }

                    foreach($reservations_form as $key => $data) {

                        if(!in_array($data->translation, $csvReservationHeader)) {
                            array_push($csvReservationHeader, $data->translation);
                        }
                        array_push($csvReservation, $data->value);
                        array_push($excelReservationsData, '<td>'.$data->value.'</td>');

                        if (!array_key_exists(str_replace(" ","",strtolower($data->translation)), $jsonReservationsData)) {
                            $jsonReservationsData[str_replace(" ","",strtolower($data->translation))] = array();
                        }
                        array_push($jsonReservationsData[str_replace(" ","",strtolower($data->translation))], $data->value);
                    }
                    array_push($csvReservations, implode(',', $csvReservation));
                    array_push($excelReservationsData, '</tr>');
                }

                array_push($excelReservations, '<table>');
                array_push($excelReservations, '    <tr>');

                foreach($csvReservationHeader as $headerName) {
                    array_push($excelReservations, '        <td>'.$headerName.'</td>');
                }
                array_push($excelReservations, '    </tr>');
                array_push($excelReservations, implode('', $excelReservationsData));

                array_push($excelReservations, '</table>');

                array_unshift($csvReservations, implode(',', $csvReservationHeader));
                
                if(strtolower($type) == 'csv') {
                    echo implode('\r\n', $csvReservations);
                } else if(strtolower($type) == 'json') {
                    echo json_encode($jsonReservationsData);   
                } else {
                    echo implode('', $excelReservations);
                }
                
                exit;
            }
            
            /*
             * Set reservations status to expired if check out day has passed.
             */
            function clean(){
                global $wpdb;
                global $DOPBSP;
                
                $wpdb->query('DELETE FROM '.$DOPBSP->tables->reservations. ' WHERE token <> "" AND ((check_out < "'.date('Y-m-d').'" AND check_out <> "") OR (check_in < "'.date('Y-m-d').'" AND check_out = ""))');
                $wpdb->query('UPDATE '.$DOPBSP->tables->reservations.' SET status="expired" WHERE status <> "expired" AND ((check_out < "'.date('Y-m-d').'" AND check_out <> "") OR (check_in < "'.date('Y-m-d').'" AND check_out = ""))');
            }
        }
    }