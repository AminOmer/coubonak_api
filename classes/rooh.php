<?php

if ( ! class_exists( 'rooh' ) ) {
	/**
	 * rooh Class.
	 *
	 */
	class rooh {

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function start( $key = false ){
			if(!isset($_GET['request']))return;
			if(isset($_GET['api'])){require_once CAPI_DIR . '/api.html';exit;}
			if(!isset($_GET['key']) || $_GET['key'] != $key){echo 'error !!'; exit;}

			header("Content-Type: application/json;charset=utf-8");
			$order = rooh::arr_get($_GET,'request', '');
			if($order == 'get_post'){
				$output = rooh::get_post($_GET);
			}elseif($order == 'get_posts'){
				$output = rooh::get_posts($_GET);
			}elseif($order == 'get_posts_count'){
				$output = rooh::get_posts_count($_GET);
			}elseif($order == 'get_pages_count'){
				$output = rooh::get_pages_count($_GET);
			}elseif($order == 'get_category'){
				$output = rooh::get_category($_GET);
			}elseif($order == 'get_dealstore'){
				$output = rooh::get_dealstore($_GET);
			}

			if(isset($_GET['as_file'])){
				$fp = fopen(CAPI_DIR . '/data.json', 'w');
				fwrite($fp, json_encode($output));
				fclose($fp);

				$file_url = home_url() . '/data.json';
				header('Content-Type: application/octet-stream');
				header("Content-Transfer-Encoding: Binary"); 
				header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\""); 
				readfile($file_url);
			}else{
				if(isset($output['result']) && isset($output['status']) && $output['status'] == true){
					$output = $output['result'];
				}
				$output = array('contacts' => $output);
				echo json_encode($output);
			}
			
			exit;
		}
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function get_categories( $params = false ){
			$order_by 	= rooh::arr_get($params,'order_by', false);
			$order 		= rooh::arr_get($params,'order', false);
			$hide_empty = rooh::arr_get($params,'hide_empty', true);
			$include 	= rooh::arr_get($params,'include', false);
			$exclude 	= rooh::arr_get($params,'exclude', false);
			$fields	= rooh::arr_get($params,'fields', 'all');

			$args = array();
			if($include){
				$include = explode(',', $include);
				$include = array_map('trim', $include);
				$args['include'] = $include;
			}
			if($exclude){
				$exclude = explode(',', $exclude);
				$exclude = array_map('trim', $exclude);
				$args['exclude'] = $exclude;
			}
			if($order_by)$args['order_by'] = $order_by;
			if($order)$args['order'] = $order;
			if($hide_empty)$args['hide_empty'] = $hide_empty;
			$cats = get_categories($args);
			if(!$cats){
				$output['status'] = false;
				$output['result'] = false;
				return $output;
			}
			$result = array();
			foreach($cats as $cat){
				$cat = rooh::get_category(array('id' => $cat->term_id, 'fields' => $fields));
				$result[] = $cat['result'];
			}
			$output['status'] = true;
			$output['result'] = $result;
			return $output;
		}
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function get_category( $params = false ){
			$id 	= rooh::arr_get($params,'id', false);
			$fields	= rooh::arr_get($params,'fields', 'all');
			if($fields == 'all' || !$fields){
				$fields = 'id, name, link, count, parent';
			}
			$fields = explode(',', $fields);
			$fields = array_map('trim', $fields);

			$category = get_term_by('id', $id, 'category');

			if(!$category){
				$output['status'] = false;
				$output['result'] = false;
				return $output;
			}
			$output['status'] = true;
			$category_link = get_category_link( $category->term_id );
			if(in_array('id', $fields))	$output['result']['id'] = $category->term_id;
			if(in_array('name', $fields)) $output['result']['name'] = $category->name;
			if(in_array('count', $fields)) $output['result']['count'] = $category->count;
			if(in_array('parent', $fields)) $output['result']['parent'] = $category->parent;
			if(in_array('link', $fields)) $output['result']['link'] = $category_link;
			return $output;
		}
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function get_dealstore( $params = false ){
			$id 	= rooh::arr_get($params,'id', false);
			$fields	= rooh::arr_get($params,'fields', 'all');
			if($fields == 'all' || !$fields){
				$fields = 'name, image, description, link';
			}
			$fields = explode(',', $fields);
			$fields = array_map('trim', $fields);

			$terms = get_terms(array(
				'taxonomy' => 'dealstore',
				'hide_empty' => false,
			));
			
			$output = array();
			if(!$terms || !is_array($terms) || empty($terms)){
				$output['status'] = false;
				$output['result'] = false;
				return $output;
			}
			$output['status'] = true;
			$results = array();
			foreach($terms as $term){
				$term_link = get_term_link( $term->term_id );
				$result = array();
				if(in_array('name', $fields)) $result['name'] = $term->name;
				if(in_array('image', $fields)) $result['image'] = get_term_meta($term->term_id, 'brandimage', true);
				if(in_array('description', $fields)) $result['description'] = $term->description;
				if(in_array('link', $fields)) $result['link'] = get_term_meta($term->term_id, 'brand_url', true);
				$results[] = $result;
			}
			$output['result'] = $results;
			return $output;
		}
		
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function get_pages_count( $params = false ){
			$post__in			= rooh::arr_get($params,'post__in', false);
			$post__not_in		= rooh::arr_get($params,'post__not_in', false);
			$category__in		= rooh::arr_get($params,'category__in', false);
			$category__not_in	= rooh::arr_get($params,'category__not_in', false);
			$fields				= rooh::arr_get($params,'fields', 'all');
			$order_by			= rooh::arr_get($params,'order_by', 'publish_date');
			$order  			= rooh::arr_get($params,'order', 'ASC');
			$count				= rooh::arr_get($params,'count', 10);
			$page				= rooh::arr_get($params,'page', 1);
			$args = array();
			if($post__in){
				$post__in = explode(',', $post__in);
				$post__in = array_map('trim', $post__in);
				$args['post__in'] = $post__in;
			}
			if($post__not_in){
				$post__not_in = explode(',', $post__not_in);
				$post__not_in = array_map('trim', $post__not_in);
				$args['post__not_in'] = $post__not_in;
			}
			if($category__in){
				$category__in = explode(',', $category__in);
				$category__in = array_map('trim', $category__in);
				$args['category__in'] = $category__in;
			}
			if($category__not_in){
				$category__not_in = explode(',', $category__not_in);
				$category__not_in = array_map('trim', $category__not_in);
				$args['category__not_in'] = $category__not_in;
			}
			$args['order_by'] 		= $order_by;
			$args['order'] 			= $order;
			$args['posts_per_page'] = -1;
			$args['paged'] 			= $page;
			$args['fields']			= 'ids';
			if(!$count)$count = -1;
			$ids = get_posts($args);
			$ids_count = ($ids)?count($ids):0;
			$pages_count = ceil($ids_count / $count);
			if($count == '-1')$pages_count = 1;
			$output = array();
			$output['status'] = true;
			$output['result'] = array('count' => $pages_count);
			return $output;
		}
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function get_posts_count( $params = false ){
			$post__in			= rooh::arr_get($params,'post__in', false);
			$post__not_in		= rooh::arr_get($params,'post__not_in', false);
			$category__in		= rooh::arr_get($params,'category__in', false);
			$category__not_in	= rooh::arr_get($params,'category__not_in', false);
			$fields				= rooh::arr_get($params,'fields', 'all');
			$order_by			= rooh::arr_get($params,'order_by', 'publish_date');
			$order  			= rooh::arr_get($params,'order', 'ASC');
			$count				= rooh::arr_get($params,'count', 10);
			$page				= rooh::arr_get($params,'page', 1);
			$args = array();
			if($post__in){
				$post__in = explode(',', $post__in);
				$post__in = array_map('trim', $post__in);
				$args['post__in'] = $post__in;
			}
			if($post__not_in){
				$post__not_in = explode(',', $post__not_in);
				$post__not_in = array_map('trim', $post__not_in);
				$args['post__not_in'] = $post__not_in;
			}
			if($category__in){
				$category__in = explode(',', $category__in);
				$category__in = array_map('trim', $category__in);
				$args['category__in'] = $category__in;
			}
			if($category__not_in){
				$category__not_in = explode(',', $category__not_in);
				$category__not_in = array_map('trim', $category__not_in);
				$args['category__not_in'] = $category__not_in;
			}
			if(!$count)$count = -1;
			$args['order_by'] 		= $order_by;
			$args['order'] 			= $order;
			$args['posts_per_page'] = $count;
			$args['paged'] 			= $page;
			$args['fields']			= 'ids';
			$ids = get_posts($args);
			$output = array();
			$output['status'] = true;
			$output['result'] = array('count' => ($ids)?count($ids):0);
			return $output;
		}
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function get_posts( $params = false ){
			$min_id				= rooh::arr_get($params,'min_id', false);
			$max_id				= rooh::arr_get($params,'max_id', false);
			$post__in			= rooh::arr_get($params,'post__in', false);
			$post__not_in		= rooh::arr_get($params,'post__not_in', false);
			$category__in		= rooh::arr_get($params,'category__in', false);
			$category__not_in	= rooh::arr_get($params,'category__not_in', false);
			$fields				= rooh::arr_get($params,'fields', 'all');
			$order_by			= rooh::arr_get($params,'order_by', 'publish_date');
			$order  			= rooh::arr_get($params,'order', 'ASC');
			$count				= rooh::arr_get($params,'count', 10);
			$page				= rooh::arr_get($params,'page', 1);
			$args = array();
			if($post__in){
				$post__in = explode(',', $post__in);
				$post__in = array_map('trim', $post__in);
				$args['post__in'] = $post__in;
			}
			if($post__not_in){
				$post__not_in = explode(',', $post__not_in);
				$post__not_in = array_map('trim', $post__not_in);
				$args['post__not_in'] = $post__not_in;
			}
			if($min_id && $min_id > 1){
				$post_ids = range(1, $min_id - 1);
				if(isset($args['post__not_in'])){
					$post_ids = array_merge($args['post__not_in'], $post_ids);
				}
				$args['post__not_in'] = $post_ids;
			}
			if($max_id && $max_id > 1){
				$post_ids = range(1, $max_id);
				if(isset($args['post__in'])){
					$post_ids = array_merge($args['post__in'], $post_ids);
				}
				$args['post__in'] = $post_ids;
			}
			if($category__in){
				$category__in = explode(',', $category__in);
				$category__in = array_map('trim', $category__in);
				$args['category__in'] = $category__in;
			}
			if($category__not_in){
				$category__not_in = explode(',', $category__not_in);
				$category__not_in = array_map('trim', $category__not_in);
				$args['category__not_in'] = $category__not_in;
			}
			$args['order_by'] 		= $order_by;
			$args['order'] 			= $order;
			$args['posts_per_page'] = $count;
			$args['paged'] 			= $page;
			$args['fields']			= 'ids';
			$output['status'] = false;
			$ids = get_posts($args);
			$output = array();
			$posts = array();
			$output['status'] = true;
			foreach($ids as $id){
				$post = rooh::get_post(array('id' => $id, 'fields' => $fields));
				if($post['status'])
				$posts[] = $post['result'];
			}
			$output['result'] = $posts;
			return $output;

		}
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        public static function get_post( $params = false ){
			$id		= rooh::arr_get($params,'id', false);
			$fields	= rooh::arr_get($params,'fields', '');
			$cat_fields	= rooh::arr_get($params,'cat_fields', 'all');
			$basics = false;
			if($fields == 'basics'){
				$basics = true;
				$fields = 'title, code, countries, expiration, dealstore, thumbnail, link, categories';
				if(!$cat_fields) $cat_fields = 'name';
			}
			if($fields == 'all' || !$fields){
				$fields = 'id, title, code, expiration, link, countries, post_date, post_date_gmt, post_content, post_status, post_name, post_modified, post_modified_gmt, category, dealstore, categories, thumbnail';
			}
			if($cat_fields == 'all' || !$cat_fields){
				$cat_fields = 'id, name, link';
			}
			$fields = explode(',', $fields);
			$fields = array_map('trim', $fields);
			$cat_fields = explode(',', $cat_fields);
			$cat_fields = array_map('trim', $cat_fields);
			$output = array();
			$output['status'] = false;
			if($id === false){
				$args = array(
					'orderby' 	=> 'publish_date',
					'order'		=> 'DESC',
					'posts_per_page' => 1
				);
				$latest_cpt = get_posts($args);
				$id = $latest_cpt[0]->ID;
			}
			
			$post = get_post($id);
			
			if(!$post){
				$output['result'] = '';
				return $output;
			}
			$output['status'] = true;
			$cats = wp_get_post_categories($post->ID);
			$post_thumbnail = get_the_post_thumbnail_url($post->ID);
			$url = get_permalink($post->ID);
			$post_category = array();
			$post_categories = array();
			$cats_names = array();
			$all_countries = explode('،', self::s('الامارات، البحرين، السعودية، الكويت، عمان، قطر، مصر'));
			$all_countries = array_map('trim', $all_countries);
			if($cats){
				foreach($cats as $k => $c){
					$cat = get_category( $c );
					$cat_link = get_category_link( $cat->term_id );
					$cats_arr = array();
					if(in_array('id', $cat_fields))$cats_arr['id'] = $cat->term_id;
					if(in_array('name', $cat_fields))$cats_arr['name'] = $cat->name;
					if(in_array('link', $cat_fields))$cats_arr['link'] = $cat->cat_link;
					if(count($cats_arr) == 1)$cats_arr = $cats_arr[array_key_first($cats_arr)];
					// if(!empty($cats_arr))$post_categories[] = $cats_arr;
					
					if(!$k)$post_category = $cats_arr;

					$this_country = explode(' ', self::s($cat->name));
					$this_country = array_map('trim', $this_country);

					$this_country = array_intersect($this_country,$all_countries);
					if(!empty($this_country) && count($this_country) == 1 && isset($this_country[array_key_first($this_country)]))
					$cats_names[] = $this_country[array_key_first($this_country)];
					else $post_categories[] = $cat->name;
				}
			}

			$dealstore = '';
			$dealstore_list = wp_get_post_terms( $post->ID, 'dealstore', array( 'fields' => 'all' ) );
			if(is_array($dealstore_list))
			foreach($dealstore_list as $dealstore_list_item){
				$dealstore = $dealstore_list_item->name;
				break;
			}
			$review_post = get_post_meta($post->ID, 'review_post', true);
			$code		= get_post_meta($post->ID, 'rehub_offer_product_coupon', true);
			$countries	= $cats_names; //get_post_meta($post->ID, '_notice_custom', true);
			$expiration = (is_array($review_post) && isset($review_post[0]['review_post_pros_text']))?$review_post[0]['review_post_pros_text']:'';
			$link 		= get_post_meta($post->ID, 'rehub_offer_product_url', true);

			if(!$code)$code = '';
			if(!$countries)$countries = '';
			if(!$expiration)$expiration = '';
			if(!$link)$link = '';
			
			if(in_array('id', $fields))	$output['result']['id'] = $post->ID;
			if(in_array('title', $fields))$output['result']['title'] = $post->post_title;
			if(in_array('code', $fields)) $output['result']['code'] = $code;
			if(in_array('countries', $fields)) $output['result']['countries'] = $countries;
			if(in_array('expiration', $fields))	$output['result']['expiration'] = $expiration;
			if(in_array('dealstore', $fields))	$output['result']['cat'] = $dealstore;
			if(in_array('link', $fields)) $output['result']['link'] = $link;
			if(in_array('post_date', $fields))$output['result']['post_date'] = $post->post_date;
			if(in_array('post_date_gmt', $fields))$output['result']['post_date_gmt'] = $post->post_date_gmt;
			if(in_array('post_content', $fields))$output['result']['post_content'] = $post->post_content;
			if(in_array('post_status', $fields))$output['result']['post_status'] = $post->post_status;
			if(in_array('post_name', $fields))$output['result']['post_name'] = $post->post_name;
			if(in_array('post_modified', $fields))$output['result']['post_modified'] = $post->post_modified;
			if(in_array('post_modified_gmt', $fields))$output['result']['post_modified_gmt'] = $post->post_modified_gmt;
			if(in_array('categories', $fields))$output['result']['categories'] = $post_categories;
			if(in_array('category', $fields))$output['result']['category'] = $post_category;
			if(in_array('thumbnail', $fields))$output['result']['thumbnail'] = $post_thumbnail;
			if(in_array('url', $fields))$output['result']['url'] = $url;
			return $output;
		}
		//////////////////////////////////////////////////////////////////////////////////////////////
		public static function create_table(){
            global $wpdb;
            $query = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'rooh_updates (
                `id` bigint(20) NOT NULL auto_increment,
                `type` varchar(60) NOT NULL,
                `type_id` varchar(60) NOT NULL,
                `action` varchar(60) NOT NULL,
				`date` datetime NOT NULL,
				`date_gmt` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) COLLATE utf8_general_ci;';
    
            return $wpdb->query( $query );
		}
		//////////////////////////////////////////////////////////////////////////////////////////////
		public static function get_updates($params = false){
			global $wpdb;
			$id		= rooh::arr_get($params,'id', '');
			$type	= rooh::arr_get($params,'type', false);
			$query = '
				SELECT * FROM `' . $wpdb->prefix . 'rooh_updates`
				WHERE `id` > '.$id.'
                ORDER BY `id` DESC
                ';
			$result =  $wpdb->get_results( $query );
			if($result){
				$updates = array();
				$updates_ = array();
				foreach($result as $update){
					if($type){
						if($update->type != $type)continue;
					}
					$this_update = array();
					$this_update['id'] 			= $update->id;
					$this_update['type'] 		= $update->type;
					$this_update['type_id']		= $update->type_id;
					$this_update['action']		= $update->action;
					$this_update['date'] 		= $update->date;
					$this_update['date_gmt'] 	= $update->date_gmt;
					$this_update['data']		= '';
					if($update->action == 'updated' && $update->type == 'post'){
						$post_id = $update->type_id;
						$data = array();
						$post = rooh::get_post(array('id'=> $post_id));
						$data['id']				= $post['result']['id'];
						$data['post_content'] 	= $post['result']['post_content'];
						$data['category'] 		= $post['result']['category'];
						$data['categories'] 	= $post['result']['categories'];
						$data['thumbnail'] 		= $post['result']['thumbnail'];
						$this_update['data']	= $data;
					}
					if($update->action == 'updated' && $update->type == 'category'){
						$category_id = $update->type_id;
						$data = array();
						$data['id']		= $category_id;
						$data['name']	= get_the_category_by_ID($category_id);
						$this_update['data']	= $data;
					}
					if(!isset($updates_[$update->type][$update->type_id])){
						$updates_[$update->type][$update->type_id] = true;
						$updates[] = $this_update;
					}
					
				}
				$output['status'] = true;
				$output['value'] = $updates;
				return $output;
			}
			$output['status'] = false;
			$output['value'] = 'nothing found';
			return $output;
		}
		//////////////////////////////////////////////////////////////////////////////////////////////
		public static function set_update($params = false){
			global $wpdb;
			$type		= rooh::arr_get($params,'type', '');
			$type_id	= rooh::arr_get($params,'type_id', 'post');
			$action		= rooh::arr_get($params,'action', '');
			$update = array(
                'id'		=> NULL,
                'type'		=> $type,
                'type_id'	=> $type_id,
                'action'	=> $action,
                'date'		=> current_time( 'mysql' ),
                'date_gmt'	=> current_time( 'mysql', 1 ),
            );
            
            if ( $wpdb->insert( $wpdb->prefix . 'rooh_updates', $update, array( '%d', '%s', '%s', '%s', '%s', '%s' ) ) ){
				return true;
			}
			return false;

		}
		//////////////////////////////////////////////////////////////////////////////////////////////
		public static function arr_get( $array, $item, $equals = false ){
			if(!is_array($array))return $equals;
			if(!isset($array[$item]))return $equals;
			return $array[$item];   
		}

        //////////////////////////////////////////////////////////////////////////////////////////////
        // remove special arabic letters
        public static function s($string){
            return self::convertStringToCheck($string);
        }

        //////////////////////////////////////////////////////////////////////////////////////////////
        // remove special arabic letters
        public static function convertStringToCheck($string){
            $string = str_replace('أ','ا',$string);
            $string = str_replace('إ','ا',$string);
            $string = str_replace('آ','ا',$string);
            $string = str_replace('ٌ','',$string);
            $string = str_replace('ُ','',$string);
            $string = str_replace('ً','',$string);
            $string = str_replace('َ','',$string);
            $string = str_replace('ِ','',$string);
            $string = str_replace('ٍ','',$string);
            $string = str_replace('~','',$string);
            $string = str_replace('ْ','',$string);
            $string = str_replace('ّ','',$string);
            $string = str_replace('ـ','',$string);
            $string = str_replace('ة','ه',$string);
            $string = str_replace('ئ','ى',$string);
            $string = str_replace('ؤ','و',$string);
            $string = str_replace('ي','ى',$string);
            return $string;
        }
    } // end class
} // end if