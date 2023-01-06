<?php

class pus_User_Controller
{


    public function __construct()
    {
        $this->namespace = '/v1';
        $this->resource_name = "user";
		$this->apiKey = 'kvgcksgvckwdgvciwvedkcvwkckwvc3t5821645';
    }

    public function register_routes()
    {
        $routes = ['updateStatus'];

        foreach ($routes as $route) {
            register_rest_route($this->namespace, '/' . $this->resource_name . '/' . $route, array(
                'methods' => 'POST',
                'callback' => array(
                    $this,
                    'pus_' . $route
                ),
            ));
        }
    }

    /**
     * 
     * Update User Download Status
     * 
     */
    
    public function pus_updateStatus($request)
    {
        global $wpdb;
		
		if($this->apiKey==$request['apiKey']){
		$user = get_user_by('email',$request['email']);
		$havemeta = get_user_meta($user->ID, $request['guid'], false);	
		
		if(!$havemeta){
		add_user_meta($user->ID, $request['guid'], 'true');
			$response = array(
            'status' => 1,
            'message' => 'Status updated successfully.'
        );
		}
		else{
		$response = array(
            'status' => 1,
            'message' => 'Status already updated.'
        );
		}
		
		}
		else{
			$response = array(
			'status'=> 0,
			'message' => 'You are not authorized user'
			);
		}

        return $response;
    }
}
