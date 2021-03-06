<?php

namespace Intranet\User;

class General
{
    public function __construct()
    {
        add_action('admin_init', array($this, 'protectWpAdmin'));

        add_action('update_user_meta', array($this, 'updateUserMeta'), 10, 4);
        add_action('profile_update', array($this, 'profileUpdate'));

        add_action('init', array($this, 'removeAdminBar'), 1200);
        add_action('init', array($this, 'makePrivateReadable'), 1200);

        add_action('pre_user_query', array($this, 'excludeInactiveUsers'));
    }

    public function excludeInactiveUsers($query)
    {
        $query->query_where .= " AND NOT user_email IS NULL AND NOT user_email LIKE 'DISABLED-USER-%'";
    }

    public function removeAdminBar()
    {
        if (!current_user_can('edit_posts') && !current_user_can_for_blog(get_current_blog_id(), 'edit_posts')) {
            show_admin_bar(false);
        }
    }

    public function makePrivateReadable()
    {
        $role = get_role('subscriber');
        if (is_object($role)) {
            $role->add_cap('read_private_posts');
            $role->add_cap('read_private_pages');
        }
    }

    public function updateUserMeta($metaId, $userId, $metaKey, $_meta_value)
    {
        remove_action('update_user_meta', array($this, 'updateUserMeta'), 10);
        update_user_meta($userId, '_profile_updated', date('Y-m-d H:i:s'));
        add_action('update_user_meta', array($this, 'updateUserMeta'), 10, 4);
    }

    public function profileUpdate($userId)
    {
        remove_action('profile_update', array($this, 'profileUpdate'), 10);
        update_user_meta($userId, '_profile_updated', date('Y-m-d H:i:s'));
        add_action('profile_update', array($this, 'profileUpdate'));
    }

    public function protectWpAdmin()
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_redirect(home_url());
        }
    }

    /**
     * Search users
     * @param  string $keyword Search keyword
     * @return array           Matching users
     */
    public static function searchUsers($keyword)
    {
        if (!is_user_logged_in()) {
            return array();
        }

        $keywordsRegex = explode(' ', trim($keyword));
        $stopwords = municipio_intranet_get_user_search_stopwords();
        $keywordsRegex = array_filter($keywordsRegex, function ($keyword) use ($stopwords) {
            return !in_array($keyword, $stopwords);
        });

        $keywordsRegex = '(' . implode('|', $keywordsRegex) . ')';

        $userMetaSearch = new \WP_User_Query(array(
            's' => $keyword,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'AND',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'first_name',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => 'last_name',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => 'user_responsibilities',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => 'user_skills',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => 'user_work_title',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => 'user_phone',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => 'user_department',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => 'ad_company',
                        'value' => '[[:<:]]' . $keywordsRegex . '[[:>:]]',
                        'compare' => 'REGEXP'
                    )
                )
            )
        ));

        $users = array();
        foreach ($userMetaSearch->results as $user) {
            $users[$user->ID] = $user->data;
        }

        foreach ($userMetaSearch->get_results() as $user) {
            if (array_key_exists($user->ID, $users)) {
                continue;
            }
            $users[$user->ID] = $user->data;
        }

        foreach ($users as $user) {
            $users[$user->ID]->name = municipio_intranet_get_user_full_name($user->ID);
            $users[$user->ID]->profile_url = municipio_intranet_get_user_profile_url($user->ID);
            $users[$user->ID]->profile_image = get_the_author_meta('user_profile_picture', $user->ID);
        }

        usort($users, function ($a, $b) use ($keyword) {
             return similar_text($keyword , $b->name) - similar_text($keyword ,$a->name);
        });

        foreach ($users as $userkey => $user) {
            if (isset($user->user_email)) {
                //Remove disabled users
                if (0 === strpos($user->user_email, 'DISABLED-USER-')) {
                    unset($users[$userkey]);
                }

                //Remove users not having an email
                if (empty($user->user_email)) {
                    unset($users[$userkey]);
                }

                //Remove users that are considerd an s-account
                if (preg_match('/^s([a-z]{4})([0-9]{4})/i', $user->user_login)) {
                    unset($users[$userkey]);
                }
            }
        }

        return $users;
    }

    public static function greet()
    {
        // General greetings
        $greetings = array(

            // All day greetings
            'day' => array(
                __('Hi %s', 'municipio-intranet'),
            ),

            // Morning greetings
            'morning' => array(
                __('Good morning %s', 'municipio-intranet'),
            ),

            // Afternoon greetings
            'afternoon' => array(
                __('Good afternoon %s', 'municipio-intranet'),
            ),

            // Eavning greetings
            'eavning' => array(
                __('Good eavning %s', 'municipio-intranet')
            ),

            // Night greetings
            'night' => array(
                __('Good night %s', 'municipio-intranet'),
            ),

        );

        $greetingKey = array('day');
        $time = current_time('H:i');

        if ($time >= '00:00' && $time <= '04:59') {
            $greetingKey = array('day', 'night');
        } elseif ($time >= '05:00' && $time <= '08:00') {
            $greetingKey = array('morning');
        } elseif ($time >= '12:30' && $time <= '17:59') {
            $greetingKey = array('day', 'afternoon');
        } elseif ($time >= '18:00' && $time <= '21:59') {
            $greetingKey = array('day', 'eavning');
        } elseif ($time >= '22:00' && $time <= '23:59') {
            $greetingKey = array('day', 'night');
        }

        // Pick a random greeting from the greeting key
        $greetingKey = $greetingKey[array_rand($greetingKey, 1)];
        $greeting = $greetings[$greetingKey][array_rand($greetings[$greetingKey], 1)];

        // Special occations
        // New year
        if (date('m-d') == '12-31' || date('m-d') == '01-01') {
            $greeting = __('Happy new year %s', 'municipio-intranet');
        }

        // Christmas
        if (date('m-d') == '12-24') {
            $greeting = __('Merry christmas %s', 'municipio-intranet');
        }

        return sprintf($greeting, '<strong>' . get_user_meta(get_current_user_id(), 'first_name', true) . '</strong>');
    }
}
