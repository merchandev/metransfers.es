<?php
namespace MeTransfers\Booking {
    class I18n { public static function language(){return 'es';} public static function text($key){return $key;} }
    class RouteDistance { public static function calculate($a,$b){return ($GLOBALS['scenario']??'')==='metrics' ? ['distance_km'=>18.5,'duration_minutes'=>30] : ['error'=>'Simulated Maps outage'];} }
    class BookingEvents { public static function pending($id){return true;} }
}
namespace MeTransfers\Payments\Redsys {
    class Gateway {
        public function is_configured(){return true;}
        public static function confirmation_url($id){return '/confirmation';}
        public function generate_payment_form($id,$cents,$order,$name){return ['url'=>'/test-payment','version'=>'HMAC_SHA256_V1','params'=>(string)$cents,'signature'=>'test'];}
    }
}
namespace {
    define('THEME', dirname(__DIR__, 2).'/');
    define('HQP_PLUGIN_DIR', THEME.'app/Legacy/Hotel/');
    define('HQP_PLUGIN_URL', '/theme/app/Legacy/Hotel/');
    define('HQP_VERSION','4.0.4');
    function wp_strip_all_tags($v){return strip_tags($v);}
    function sanitize_text_field($v){return is_scalar($v)?trim(strip_tags((string)$v)):'';}
    function sanitize_textarea_field($v){return sanitize_text_field($v);}
    function sanitize_email($v){return trim($v);}
    function is_email($v){return filter_var($v,FILTER_VALIDATE_EMAIL);}
    function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower($v));}
    function wp_unslash($v){return $v;}
    function absint($v){return abs((int)$v);}
    function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
    function metadata_exists($type,$id,$key){return array_key_exists($key,$GLOBALS['meta'][$id]??[]);}
    function apply_filters($name,$value){return $value;}
    function check_ajax_referer($action,$key){if(($_POST[$key]??'')!=='test-nonce') throw new \RuntimeException('Bad nonce');}
    function current_time($type){return date('Y-m-d H:i:s');}
    function wp_enqueue_script(...$args){}
    function wp_enqueue_style(...$args){}
    function wp_localize_script($handle,$name,$data){$GLOBALS['localized']=$data;}
    function wp_create_nonce($v){return 'test-nonce';}
    function admin_url($v){return '/ajax';}
    function get_the_title($id){return 'Hotel Regina';}
    function esc_html($v){return htmlspecialchars((string)$v, ENT_QUOTES,'UTF-8');}
    function esc_attr($v){return esc_html($v);}
    function finish($success,$data,$status){echo json_encode(['success'=>$success,'data'=>$data,'status'=>$status??200,'saved'=>$GLOBALS['wpdb']->saved]);exit;}
    class JsonResult extends \Exception { public $payload; public $status; public function __construct($success,$data,$status){$this->payload=['success'=>$success,'data'=>$data];$this->status=$status?:200;} }
    function wp_send_json_success($data,$status=null){finish(true,$data,$status);}
    function wp_send_json_error($data,$status=null){finish(false,$data,$status);}
    class WP_Query { public $posts=[]; public function __construct($args){foreach($GLOBALS['meta'] as $id=>$data){if(($data['_hqp_token']??'')===$args['meta_value'])$this->posts[]=$id;}} public function have_posts(){return count($this->posts)>0;} }
    class FakeDb {
        public function prepare($sql,...$args){return vsprintf(str_replace('%i','%s',$sql),$args);}
        public $prefix='wp_'; public $last_error=''; public $insert_id=10001; public $saved=[];
        public function get_results($sql){return array_values(array_filter($GLOBALS['fleet'],fn($v)=>$v->is_active===1));}
        public function insert($table,$data,$formats){$this->saved[]=$data;return 1;}
        public function update(...$args){return 1;}
    }
    function fixture(){
        $GLOBALS['wpdb']=new FakeDb;
        $GLOBALS['fleet']=[];
        foreach([[1,'MINI VAN ECONOMIC',7],[2,'BUSINESS CLASS',2],[3,'ECONOMIC CLASS',3],[4,'MINI VAN V-Class',7]] as [$id,$name,$capacity]) $GLOBALS['fleet'][]=(object)compact('id','name','capacity');
        foreach($GLOBALS['fleet'] as $v){$v->is_active=1;$v->description='Traslado privado';}
        $GLOBALS['meta']=[100=>['_hqp_token'=>'TEST-HOTEL-REGINA','_hqp_hotel_address'=>'Carrer de Bergara, 4, Barcelona','_hqp_price_vehicle_1'=>'90','_hqp_price_vehicle_2'=>'61','_hqp_price_vehicle_3'=>'0','_hqp_price_vehicle_4'=>'90'],200=>['_hqp_token'=>'TEST-OTHER-HOTEL']];
        $_COOKIE=['hqp_hotel_token'=>'TEST-OTHER-HOTEL'];
        $_GET=['promo'=>'TEST-HOTEL-REGINA'];
    }
    require THEME.'app/Pricing/Money.php';
    require THEME.'app/HotelPortal/Services/HotelFixedPricing.php';
    require THEME.'app/HotelPortal/Services/HotelBookingAttribution.php';
    require THEME.'app/Booking/BookingDatePolicy.php';
    require HQP_PLUGIN_DIR.'public/class-hqp-public.php';
    fixture();
    $scenario=$argv[1]??'outage';
    $_POST=['security'=>'test-nonce','hotel_id'=>100,'hotel_token'=>'TEST-HOTEL-REGINA','passengers'=>3,'date'=>(new DateTimeImmutable('+2 days'))->format('Y-m-d'),'time'=>'15:30','vehicle_id'=>1,'quoted_price_cents'=>'9000','customer_name'=>'Test Guest','customer_email'=>'guest@example.test','customer_phone'=>'+34000000000','route_direction'=>'from_hotel','route_location'=>'airport_bcn','origin'=>'Forged origin','destination'=>'Madrid'];
    if($scenario==='invalid_route') $_POST['route_location']='madrid';
    if($scenario==='price_changed') $_POST['quoted_price_cents']='6100';
    if($scenario==='inbound') $_POST['route_direction']='to_hotel';
    if($scenario==='capacity') $_POST['vehicle_id']=2;
    (new HQP_Public)->ajax_create_booking();
}
