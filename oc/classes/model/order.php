<?php defined('SYSPATH') or die('No direct script access.');
/**
 * description...
 *
 * @author		Slobodan <slobodan@open-classifieds.com>
 * @package		OC
 * @copyright	(c) 2009-2013 Open Classifieds Team
 * @license		GPL v3
 * *
 */
class Model_Order extends ORM {


	/**
	 * Table name to use
	 *
	 * @access	protected
	 * @var		string	$_table_name default [singular model name]
	 */
	protected $_table_name = 'orders';

	/**
	 * Column to use as primary key
	 *
	 * @access	protected
	 * @var		string	$_primary_key default [id]
	 */
	protected $_primary_key = 'id_order';

	/**
	 * Status constants
	 */
    const STATUS_CREATED        = 0;   // just created
    const STATUS_PAID           = 1;   // paid!
    const STATUS_REFUSED        = 5;   //tried to paid but not succeed
    const STATUS_REFUND         = 99;  //we refunded the money

    /**
     * @var  array  Available statuses array
     */
    public static $statuses = array(
        self::STATUS_CREATED      =>  'Created',
        self::STATUS_PAID         =>  'Paid',
        self::STATUS_REFUSED      =>  'Refused',
        self::STATUS_REFUND       =>  'Refund',
    );

    /**
     * Id of products 
     */
    const PRODUCT_CATEGORY      = 1; //paid to post in a paid category
    const PRODUCT_TO_TOP        = 2; //paid to return the ad to the first page
    const PRODUCT_TO_FEATURED   = 3; // paid to featured an ad in the site
    const PRODUCT_AD_SELL       = 4; // a customer paid to buy the item/ad 

    /**
     * returns the product array
     * @return string          
     */
    public static function products()
    {
        return array(
            self::PRODUCT_CATEGORY      =>  __('Post in paid category'),
            self::PRODUCT_TO_TOP        =>  __('Top up ad'),
            self::PRODUCT_TO_FEATURED   =>  __('Feature ad'),
            self::PRODUCT_AD_SELL       =>  __('Buy product'),
        );
    }


    /**
     * returns the product descripton
     * @param  int $product 
     * @return string          
     */
    public static function product_desc($product)
    {
        $products = self::products();

        return (isset($products[$product])) ? $products[$product] : '' ;
    }

    /**
     * @var  array  ORM Dependency/hirerachy
     */
    protected $_belongs_to = array(
        'ad' => array(
                'model'       => 'ad',
                'foreign_key' => 'id_ad',
            ),
        'user' => array(
                'model'       => 'user',
                'foreign_key' => 'id_user',
            ),
    );

    /**
     * confirm payment for order
     *
     * @param string    $id_order [unique indentifier of order]
     */
    public function confirm_payment($paymethod = 'paypal')
    { 
        
        // update orders
        if($this->loaded())
        {
            $ad  = $this->ad;

            $this->status    = self::STATUS_PAID;
            $this->pay_date  = Date::unix2mysql();
            $this->paymethod = $paymethod;

            try {
                $this->save();
            } catch (Exception $e) {
                throw HTTP_Exception::factory(500,$e->getMessage());  
            }

            //send email to site owner! new sale!! 
            if(core::config('email.new_ad_notify') == TRUE)
            {                    
                $url_ad = Route::url('ad', array('category'=>$ad->category->seoname,'seotitle'=>$ad->seotitle));
                
                $replace = array('[AD.TITLE]'   => $ad->title,
                                 '[URL.AD]'     => $url_ad,
                                 '[ORDER.ID]'   => $this->id_order,
                                 '[PRODUCT.ID]' => $this->id_product);

                Email::content(core::config('email.notify_email'),
                                    core::config('general.site_name'),
                                    core::config('email.notify_email'),
                                    core::config('general.site_name'),'ads-sold',
                                    $replace);
            }

            //depending on the product different actions
            switch ($this->id_product) {
                case Model_Order::PRODUCT_AD_SELL:
                        $ad->sale($this->user);
                    break;
                case Model_Order::PRODUCT_TO_TOP:
                        $ad->to_top();
                    break;
                case Model_Order::PRODUCT_TO_FEATURED:
                        $ad->to_feature();
                    break;
                case Model_Order::PRODUCT_CATEGORY:
                        $ad->paid_category();
                    break;
            }
 
        }
    }


    /**
     * creates an order
     * @param  Model_Ad $ad    
     * @param  Model_User $user          
     * @param  integer   $id_product  
     * @param  numeric   $amount      
     * @param  string   $currency    
     * @param  string   $description 
     * @return Model_Order                
     */
    public static function new_order(Model_Ad $ad, Model_User $user, $id_product, $amount, $currency = NULL, $description = NULL)
    {
        if ($currency === NULL)
            $currency = core::config('payment.paypal_currency');

        if ($description === NULL)
            $description = Model_Order::product_desc($id_product);

        //get if theres an unpaid order for this product and this ad
        $order = new Model_Order();
        $order->where('id_ad',      '=', $ad->id_ad)
              ->where('id_user',    '=', $user->id_user)
              ->where('status',     '=', Model_Order::STATUS_CREATED)
              ->where('id_product', '=', $id_product)
              ->where('amount',     '=', $amount)
              ->where('currency',   '=', $currency)
              ->limit(1)->find();

        //if no unpaid create order
        if (!$order->loaded())
        {
            //create order      
            $order = new Model_Order;
            $order->id_user       = $user->id_user;
            $order->id_ad         = $ad->id_ad;
            $order->id_product    = $id_product;
            $order->currency      = $currency;
            $order->amount        = $amount;
            $order->description   = $description;

            try {
                $order->save();
            } 
            catch (Exception $e){
                throw HTTP_Exception::factory(500,$e->getMessage());
            }

            //send email to user with link to pay
            $url_checkout = $user->ql('default', array('controller'=>'ad','action'=>'checkout','id'=>$order->id_order));
                
            $replace = array('[ORDER.ID]'    => $order->id_order,
                             '[ORDER.DESC]'  => $order->description,
                             '[URL.CHECKOUT]'=> $url_checkout);

            $user->email('new-order',$replace);
        }     

        return $order;
    }



    public function exclude_fields()
    {
        return array('created','id_ad','id_user');
    }

    /**
     * 
     * formmanager definitions
     * 
     */
    public function form_setup($form)
    {   

        $form->fields['description']['display_as'] = 'textarea';
        $form->fields['status']['display_as']       = 'select';
        $form->fields['status']['options']          = array_keys(self::$statuses);
        $form->fields['id_product']['display_as']   = 'select';
        $form->fields['id_product']['options']      = array_keys(self::products());

    }
    
    protected $_table_columns =  
array (
  'id_order' => 
  array (
    'type' => 'int',
    'min' => '0',
    'max' => '4294967295',
    'column_name' => 'id_order',
    'column_default' => NULL,
    'data_type' => 'int unsigned',
    'is_nullable' => false,
    'ordinal_position' => 1,
    'display' => '10',
    'comment' => '',
    'extra' => 'auto_increment',
    'key' => 'PRI',
    'privileges' => 'select,insert,update,references',
  ),
  'id_user' => 
  array (
    'type' => 'int',
    'min' => '0',
    'max' => '4294967295',
    'column_name' => 'id_user',
    'column_default' => NULL,
    'data_type' => 'int unsigned',
    'is_nullable' => false,
    'ordinal_position' => 2,
    'display' => '10',
    'comment' => '',
    'extra' => '',
    'key' => 'MUL',
    'privileges' => 'select,insert,update,references',
  ),
  'id_ad' => 
  array (
    'type' => 'int',
    'min' => '0',
    'max' => '4294967295',
    'column_name' => 'id_ad',
    'column_default' => NULL,
    'data_type' => 'int unsigned',
    'is_nullable' => true,
    'ordinal_position' => 3,
    'display' => '10',
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
  'id_product' => 
  array (
    'type' => 'string',
    'column_name' => 'id_product',
    'column_default' => NULL,
    'data_type' => 'varchar',
    'is_nullable' => false,
    'ordinal_position' => 4,
    'character_maximum_length' => '20',
    'collation_name' => 'utf8_general_ci',
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
  'paymethod' => 
  array (
    'type' => 'string',
    'column_name' => 'paymethod',
    'column_default' => NULL,
    'data_type' => 'varchar',
    'is_nullable' => true,
    'ordinal_position' => 5,
    'character_maximum_length' => '20',
    'collation_name' => 'utf8_general_ci',
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
  'created' => 
  array (
    'type' => 'string',
    'column_name' => 'created',
    'column_default' => 'CURRENT_TIMESTAMP',
    'data_type' => 'timestamp',
    'is_nullable' => false,
    'ordinal_position' => 6,
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
  'pay_date' => 
  array (
    'type' => 'string',
    'column_name' => 'pay_date',
    'column_default' => NULL,
    'data_type' => 'datetime',
    'is_nullable' => true,
    'ordinal_position' => 7,
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
  'currency' => 
  array (
    'type' => 'string',
    'exact' => true,
    'column_name' => 'currency',
    'column_default' => NULL,
    'data_type' => 'char',
    'is_nullable' => false,
    'ordinal_position' => 8,
    'character_maximum_length' => '3',
    'collation_name' => 'utf8_general_ci',
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
  'amount' => 
  array (
    'type' => 'float',
    'exact' => true,
    'column_name' => 'amount',
    'column_default' => '0.000',
    'data_type' => 'decimal',
    'is_nullable' => false,
    'ordinal_position' => 9,
    'numeric_scale' => '3',
    'numeric_precision' => '14',
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
  'status' => 
  array (
    'type' => 'int',
    'min' => '-128',
    'max' => '127',
    'column_name' => 'status',
    'column_default' => '0',
    'data_type' => 'tinyint',
    'is_nullable' => false,
    'ordinal_position' => 10,
    'display' => '1',
    'comment' => '',
    'extra' => '',
    'key' => 'MUL',
    'privileges' => 'select,insert,update,references',
  ),
  'description' => 
  array (
    'type' => 'string',
    'column_name' => 'description',
    'column_default' => NULL,
    'data_type' => 'varchar',
    'is_nullable' => true,
    'ordinal_position' => 11,
    'character_maximum_length' => '145',
    'collation_name' => 'utf8_general_ci',
    'comment' => '',
    'extra' => '',
    'key' => '',
    'privileges' => 'select,insert,update,references',
  ),
);
}
