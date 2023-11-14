<?php
get_header();

global $wpdb;
$table_name = $wpdb->prefix . 't2s_stores';
$store_id = get_query_var('store_id');
$result = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $store_id");
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main container" role="main">
        <?php
        if ($result) :
        ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php echo $result->name; ?></h1>
                </header>

                <div class="entry-content">
                    <div>图片：<img src="<?php echo $result->image_url; ?>" alt=""></div>
                    <div>地址：<?php echo $result->address; ?></div>
                    <div>城市：<?php echo $result->city; ?></div>
                    <div>州：<?php echo $result->state; ?></div>
                    <div>国家：<?php echo $result->country; ?></div>
                    <div>邮编：<?php echo $result->postal_code; ?></div>
                    <div>经度：<?php echo $result->lon; ?></div>
                    <div>纬度：<?php echo $result->lat; ?></div>
                    <div>概览：<?php echo $result->overview; ?></div>
                </div>
            </article>
        <?php
        else :
        ?>
            <p>404</p>
        <?php
        endif;
        ?>
    </main>
</div>

<?php get_footer(); ?>
