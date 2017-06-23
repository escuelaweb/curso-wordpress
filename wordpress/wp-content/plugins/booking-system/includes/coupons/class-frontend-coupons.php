<?php

/*
* Title                   : Pinpoint Booking System WordPress Plugin
* Version                 : 2.1.2
* File                    : includes/coupons/class-frontend-coupons.php
* File Version            : 1.0.3
* Created / Last Modified : 11 November 2015
* Author                  : Dot on Paper
* Copyright               : © 2012 Dot on Paper
* Website                 : http://www.dotonpaper.net
* Description             : Front end coupons PHP class.
*/

    if (!class_exists('DOPBSPFrontEndCoupons')){
        class DOPBSPFrontEndCoupons extends DOPBSPFrontEnd{
            /*
             * Constructor.
             */
            function __construct(){
            }
            
            /*
             * Get selected coupons.
             * 
             * @param id(string): coupon ID
             * @param language (string): selected language
             * 
             * @return data array
             */
            function get($id,
                         $language = DOPBSP_CONFIG_TRANSLATION_DEFAULT_LANGUAGE){
                global $wpdb;
                global $DOPBSP;
                
                $coupon = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$DOPBSP->tables->coupons.' WHERE code=%s ORDER BY id',
                                                        $code));
                
                if ($id != 0){
                    
                    if($coupon->translation != '') {
                        $coupon->translation = $DOPBSP->classes->translation->decodeJSON($coupon->translation,
                                                                                         $language);
                        $coupon->code = '';
                    }
                }
                
                return array('data' => array('coupon' => $coupon,
                                             'id' => $id),
                             'text' => array('byDay' => $DOPBSP->text('COUPONS_FRONT_END_BY_DAY'),
                                             'byHour' => $DOPBSP->text('COUPONS_FRONT_END_BY_HOUR'),
                                             'code' => $DOPBSP->text('COUPONS_FRONT_END_CODE'),
                                             'title' => $DOPBSP->text('COUPONS_FRONT_END_TITLE'),
                                             'use' => $DOPBSP->text('COUPONS_FRONT_END_USE'),
                                             'verify' => $DOPBSP->text('COUPONS_FRONT_END_VERIFY'),
                                             'verifyError' => $DOPBSP->text('COUPONS_FRONT_END_VERIFY_ERROR'),
                                             'verifySuccess' => $DOPBSP->text('COUPONS_FRONT_END_VERIFY_SUCCESS')));
            }
            
            /*
             * Verify coupon code.
             * 
             * @post id (integer): coupon ID
             * @post code (string): coupon code
             * @post today (string): today date
             * @post curr_time (string): current time
             * 
             * @return "success" or "error" message
             */
            function verify(){
                global $wpdb;
                global $DOPBSP;
                
                $id = $_POST['id'];
                $code = $_POST['code'];
                $today = $_POST['today'];
                $language = $_POST['language'];
                $curr_time = $_POST['curr_time'];
                $calendar_id = $_POST['calendar_id'];
                
                $settings_calendar = $DOPBSP->classes->backend_settings->values($calendar_id,  
                                                                                'calendar');
                
                $coupon = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$DOPBSP->tables->coupons.' WHERE code=%s ORDER BY id',
                                                        $code));
                
                if (strpos($settings_calendar->coupon, ',') !== false) {
                    $coupons_active = explode(',', $settings_calendar->coupon);
                    
                    if(!in_array($coupon->id, $coupons_active)) {
                        echo 'error';
                        die();
                    }
                } else {
                    
                    if($coupon->id != $settings_calendar->coupon) {
                        echo 'error';
                        die();
                    }
                }
                
                if ($code == $coupon->code
                        && ($coupon->start_date == ''
                                    || $coupon->start_date <= $today)
                        && ($coupon->end_date == ''
                                    || $coupon->end_date >= $today)
                        && ($coupon->start_hour == ''
                                    || $coupon->start_hour <= $curr_time)
                        && ($coupon->end_hour == ''
                                    || $coupon->end_hour >= $curr_time)
                        && ($coupon->no_coupons == ''
                                    || (int)$coupon->no_coupons > 0)
                        && (int)$coupon->price > 0){
                        
                    if($coupon->translation != '') {
                        $coupon->translation = $DOPBSP->classes->translation->decodeJSON($coupon->translation,
                                                                                         $language);
                    }
                    echo json_encode($coupon);
                }
                else{
                    echo 'error';
                }
                
                die();
            }
        }
    }