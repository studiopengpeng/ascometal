<?php
namespace WPDM\libs;


class ShortCodes
{

    function __construct()
    {


        // Total Package Count
        add_shortcode('wpdm_package_count', array($this, 'TotalPackages'));

        // Total Package Count
        add_shortcode('wpdm_download_count', array($this, 'TotalDownloads'));

        // Login/Register Form
        add_shortcode('wpdm_login_form', array($this, 'LoginForm'));

         // Register form
        add_shortcode('wpdm_reg_form', array($this, 'RegisterForm'));

        // Edit Profile
        add_shortcode('wpdm_edit_profile', array($this, 'EditProfile'));

        // Show all packages
        add_shortcode('wpdm_packages', array($this, 'Packages'));

        // Show a package by id
        add_shortcode('wpdm_package', array($this, 'Package'));

        // Generate direct download link
        add_shortcode('wpdm_direct_link', array($this, 'directLink'));

        // Show all packages in a responsive table
        add_shortcode('wpdm_all_packages', array($this, 'allPackages'));
        add_shortcode('wpdm-all-packages', array($this, 'allPackages'));

        //Packages by tag
        add_shortcode("wpdm_tag", array($this, 'packagesByTag'));

    }

    /**
     * @usage Short-code function for total download count
     * @param array $params
     * @return mixed
     */
    function TotalDownloads($params = array()){
        global $wpdb;
        $download_count = $wpdb->get_var("select sum(meta_value) from {$wpdb->prefix}postmeta where meta_key='__wpdm_download_count'");
        return $download_count;
    }

    /**
     * @usage Short-code function for total package count
     * @param array $params
     * @return mixed
     */
    function TotalPackages($params = array()){
        $count_posts = wp_count_posts('wpdmpro');
        $status = isset($params['status'])?$params['status']:'publish';
        if($status=='draft') return $count_posts->draft;
        if($status=='pending') return $count_posts->pending;
        return $count_posts->publish;
    }

    /**
     * @usage Short-code callback function for login/register form
     * @return string
     */
    function LoginForm($params = array()){
        if(isset($params) && is_array($params))
            extract($params);
        if(!isset($redirect)) $redirect = get_permalink(get_option('__wpdm_user_dashboard'));
        ob_start();
        //echo "<div class='w3eden'>";
        include(WPDM_BASE_DIR . 'tpls/wpdm-be-member.php');
        //echo "</div>";
        return ob_get_clean();
    }



    /**
     * @usage Edit profile
     * @return string
     */
    public function EditProfile()
    {
        global $wpdb, $current_user, $wp_query;
        wp_reset_query();
        $currentAccess = maybe_unserialize(get_option('__wpdm_front_end_access', array()));

        if (!array_intersect($currentAccess, $current_user->roles) && is_user_logged_in())
            return WPDM_Messages::Error(wpautop(stripslashes(get_option('__wpdm_front_end_access_blocked'))), -1);


        $id = wpdm_query_var('ID');

        ob_start();

        if (is_user_logged_in()) {
            include(wpdm_tpl_path('wpdm-edit-user-profile.php'));
        } else {
            include(wpdm_tpl_path('wpdm-be-member.php'));
        }

        $data = ob_get_clean();
        return $data;
    }

    function RegisterForm($params = array()){
        if(!get_option('users_can_register')) return __('User registration is disabled', 'wpdmpro');
        if(isset($params['role'])) update_post_meta(get_the_ID(),'__wpdm_role', $params['role']);
        ob_start();
        echo "<div class='w3eden'>";
        require_once(wpdm_tpl_path('wpdm-reg-form.php'));
        echo "</div>";
        $data = ob_get_clean();
        return $data;
    }

    function Packages($params = array('items_per_page' => 10, 'title' => false, 'desc' => false, 'order_by' => 'date', 'order' => 'desc', 'paging' => false, 'toolbar' => 1, 'template' => '','cols'=>3, 'colspad'=>2, 'colsphone' => 1, 'tags' => '', 'categorirs' => '', 'year' => '', 'month' => ''))
    {
        $params['order_by']  = isset($params['order_field']) && $params['order_field'] != '' && !isset($params['order_by'])?$params['order_field']:$params['order_by'];
        $scparams = $params;
        $defaults = array('items_per_page' => 10, 'title' => false, 'desc' => false, 'order_by' => 'date', 'order' => 'desc', 'paging' => false, 'toolbar' => 1, 'template' => 'link-template-panel','cols'=>3, 'colspad'=>2, 'colsphone' => 1);
        $params = shortcode_atts($defaults, $params, 'wpdm_packages' );
        if(is_array($params))
            extract($params);

        $cwd_class = "col-md-".(int)(12/$cols);
        $cwdsm_class = "col-sm-".(int)(12/$colspad);
        $cwdxs_class = "col-xs-".(int)(12/$colsphone);

        if(isset($id)) {
            $id = trim($id, ", ");
            $cids = explode(",", $id);
        }

        global $wpdb, $current_user, $post, $wp_query;

        if(isset($order_by) && !isset($order_field)) $order_field = $order_by;
        $order_field = isset($order_field) ? $order_field : 'date';
        $order_field = isset($_GET['orderby']) ? $_GET['orderby'] : $order_field;
        $order = isset($order) ? $order : 'desc';
        $order = isset($_GET['order']) ? $_GET['order'] : $order;
        $cp = wpdm_query_var('cp','num');
        if(!$cp) $cp = 1;

        $params = array(
            'post_type' => 'wpdmpro',
            'paged' => $cp,
            'posts_per_page' => $items_per_page,
            'include_children' => false,
        );

        if(isset($scparams['month']) && $scparams['month'] != '') $params['monthnum'] = $scparams['month'];
        if(isset($scparams['year']) && $scparams['year'] != '') $params['year'] = $scparams['year'];
        if(isset($scparams['day']) && $scparams['day'] != '') $params['day'] = $scparams['day'];
        if(isset($scparams['search']) && $scparams['search'] != '') $params['s'] = $scparams['search'];
        if(isset($scparams['tag']) && $scparams['tag'] != '') $params['tag'] = $scparams['tag'];
        if(isset($scparams['tag_id']) && $scparams['tag_id'] != '') $params['tag_id'] = $scparams['tag_id'];
        if(isset($scparams['tag__and']) && $scparams['tag__and'] != '') $params['tag__and'] = explode(",",$scparams['tag__and']);
        if(isset($scparams['tag__in']) && $scparams['tag__in'] != '') $params['tag__in'] = explode(",",$scparams['tag__in']);
        if(isset($scparams['tag__not_in']) && $scparams['tag__not_in'] != '') $params['tag__not_in'] = explode(",",$scparams['tag__not_in']);
        if(isset($scparams['tag_slug__and']) && $scparams['tag_slug__and'] != '') $params['tag_slug__and'] = explode(",",$scparams['tag_slug__and']);
        if(isset($scparams['tag_slug__in']) && $scparams['tag_slug__in'] != '') $params['tag_slug__in'] = explode(",",$scparams['tag_slug__in']);
        if(isset($scparams['categories']) && $scparams['categories'] != '') {
            $operator = isset($scparams['operator'])?$scparams['operator']:'OR';
            $params['tax_query'] = array(array(
                'taxonomy' => 'wpdmcategory',
                'field' => 'slug',
                'terms' => explode(",",$scparams['categories']),
                'include_children' => ( isset($scparams['include_children']) && $scparams['include_children'] != '' )?$scparams['include_children']: false,
                'operator' => $operator
            ));
        }


        if (get_option('_wpdm_hide_all', 0) == 1) {
            $params['meta_query'] = array(
                array(
                    'key' => '__wpdm_access',
                    'value' => '"guest"',
                    'compare' => 'LIKE'
                )
            );
            if(is_user_logged_in()){
                global $current_user;
                $params['meta_query'][] = array(
                    'key' => '__wpdm_access',
                    'value' => $current_user->roles[0],
                    'compare' => 'LIKE'
                );
                $params['meta_query']['relation'] = 'OR';
            }
        }

        $order_fields = array('__wpdm_download_count','__wpdm_view_count','__wpdm_package_size_b');
        if(!in_array( "__wpdm_".$order_field, $order_fields)) {
            $params['orderby'] = $order_field;
            $params['order'] = $order;
        } else {
            $params['orderby'] = 'meta_value_num';
            $params['meta_key'] = "__wpdm_".$order_field;
            $params['order'] = $order;
        }

        $params = apply_filters("wpdm_packages_query_params", $params);

        $packs = new \WP_Query($params);

        $total = $packs->found_posts;

        $pages = ceil($total / $items_per_page);
        $page = isset($_GET['cp']) ? $_GET['cp'] : 1;
        $start = ($page - 1) * $items_per_page;

        if (!isset($paging) || intval($paging) == 1) {
            $pag = new \WPDM\libs\Pagination();
            $pag->items($total);
            $pag->nextLabel(' &#9658; ');
            $pag->prevLabel(' &#9668; ');
            $pag->limit($items_per_page);
            $pag->currentPage($page);
        }

        $burl = get_permalink();
        $url = $_SERVER['REQUEST_URI']; //get_permalink();
        $url = strpos($url, '?') ? $url . '&' : $url . '?';
        $url = preg_replace("/[\&]*cp=[0-9]+[\&]*/", "", $url);
        $url = strpos($url, '?') ? $url . '&' : $url . '?';
        if (!isset($paging) || intval($paging) == 1)
            $pag->urlTemplate($url . "cp=[%PAGENO%]");


        $html = '';
        $templates = maybe_unserialize(get_option("_fm_link_templates", true));

        if(isset($templates[$template])) $template = $templates[$template]['content'];

        //global $post;
        while($packs->have_posts()) { $packs->the_post();

            $pack = (array)$post;
            $repeater = "<div class='{$cwd_class} {$cwdsm_class} {$cwdxs_class}'>".\WPDM\Package::FetchTemplate($template, $pack)."</div>";
            $html .=  $repeater;

        }
        wp_reset_query();

        $html = "<div class='row'>{$html}</div>";


        if (!isset($paging) || intval($paging) == 1)
            $pgn = "<div style='clear:both'></div>" . $pag->show() . "<div style='clear:both'></div>";
        else
            $pgn = "";
        global $post;

        $sap = get_option('permalink_structure') ? '?' : '&';
        $burl = $burl . $sap;
        if (isset($_GET['p']) && $_GET['p'] != '') $burl .= 'p=' . $_GET['p'] . '&';
        if (isset($_GET['src']) && $_GET['src'] != '') $burl .= 'src=' . $_GET['src'] . '&';
        $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'create_date';
        $order = ucfirst($order);

        $title = isset($title) && $title !=''?"<h3>$title</h3>":"";

        return "<div class='w3eden'>" . $title . $desc  . $html  . $pgn . "<div style='clear:both'></div></div>";
    }


    /**
     * @usage Callback function for shortcode [wpdm_package id=PID]
     * @param mixed $params
     * @return mixed
     */
    function Package($params)
    {
        extract($params);
        if(!isset($id)) return '';
        $id = (int)$id;
        $postlink = site_url('/');
        if (isset($pagetemplate) && $pagetemplate == 1) {
            $template = get_post_meta($id,'__wpdm_page_template', true);
            $wpdm_package['page_template'] = stripcslashes($template);
            $data = FetchTemplate($template, $id, 'page');
            $siteurl = site_url('/');
            return  "<div class='w3eden'>{$data}</div>";
        }

        $template = isset($params['template'])?$params['template']:get_post_meta($id,'__wpdm_template', true);
        if($template == '') $template = 'link-template-calltoaction3.php';
        return "<div class='w3eden'>" . \WPDM\Package::fetchTemplate($template, $id, 'link') . "</div>";
    }

    /**
     * @usage Generate direct link to download
     * @param $params
     * @param string $content
     * @return string
     */
    function directLink($params, $content = "")
    {
        extract($params);
        global $wpdb;
        if(\WPDM\Package::isLocked($params['id']))
            $linkURL = get_permalink($params['id']);
        else
            $linkURL = "index.php?wpdmdl=".$params['id'];
        $linkLabel = isset($params['label']) && !empty($params['label'])?$params['label']:get_post_meta($params['id'], '__wpdm_link_label', true);
        $linkLabel = empty($linkLabel)?'Download '.get_the_title($params['id']):$linkLabel;
        return  "<a href='$linkURL'>$linkLabel</a>";

    }

    /**
     * @usage Short-code [wpdm_all_packages] to list all packages in tabular format
     * @param array $params
     * @return string
     */
    function allPackages($params = array())
    {
        global $wpdb, $current_user, $wp_query;
        $items = isset($params['items_per_page']) && $params['items_per_page'] > 0 ? $params['items_per_page'] : 20;
        if(isset($params['jstable']) && $params['jstable']==1) $items = 2000;
        $cp = isset($wp_query->query_vars['paged']) && $wp_query->query_vars['paged'] > 0 ? $wp_query->query_vars['paged'] : 1;
        $terms = isset($params['categories']) ? explode(",", $params['categories']) : array();
        if (isset($_GET['wpdmc'])) $terms = array(esc_attr($_GET['wpdmc']));
        $offset = ($cp - 1) * $items;
        $total_files = wp_count_posts('wpdmpro')->publish;
        if (count($terms) > 0) {
            $tax_query = array(array(
                'taxonomy' => 'wpdmcategory',
                'field' => 'slug',
                'terms' => $terms,
                'operator' => 'IN',
                'include_children' => false
            ));
        }

        ob_start();
        include(wpdm_tpl_path("wpdm-all-downloads.php"));
        $data = ob_get_clean();
        return $data;
    }

    /**
     * @usage Show packages by tag
     * @param $params
     * @return string
     */
    function packagesByTag($params)
    {
        $params['order_field'] = isset($params['order_by'])?$params['order_by']:'publish_date';
        $params['tag'] = 1;
        unset($params['order_by']);
        if (isset($params['item_per_page']) && !isset($params['items_per_page'])) $params['items_per_page'] = $params['item_per_page'];
        unset($params['item_per_page']);
        return wpdm_embed_category($params);

    }





}
