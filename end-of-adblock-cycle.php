<?php
/*
Plugin Name: End of Adblock Cycle
Plugin URI: https://wordpress.org/plugins/end-of-adblock-cycle/
Description: Adblockなど広告を非表示にしてるユーザーへの警告や、ユーザー数の把握
Version: 1.2
Author: oxynotes
Author URI: http://oxynotes.com
License: GPL2

// お決まりのGPL2の文言（省略や翻訳不可）
Copyright 2015 oxy (email : oxy@oxynotes.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/




// インストールパスのディレクトリが定義されているか調べる（プラグインのテンプレ）
if ( !defined('ABSPATH') ) { exit(); }




// アクティベート、ディアクティベート処理
register_activation_hook( __FILE__, array( new End_Of_Adblock_Cycle_Activate, 'activate' ) );
register_deactivation_hook( __FILE__, array( new End_Of_Adblock_Cycle_Activate, 'deactivate' ) );




/**
 * アクティベート、ディアクティベート専用のクラス
 */
class End_Of_Adblock_Cycle_Activate {

	public function activate() {
		$this->add_end_of_adblock_cycle();
		$this->create_js();
	}




	public function deactivate() {
		$this->remove_js();
		$this->remove_end_of_adblock_cycle();
	}




	/**
	 * ランダム文字列生成 
	 * JavaScriptのクラス名や関数名に利用するので利用可能な文字列に限る
	 * @param	int		生成する文字数
	 */
	function make_rand_str( $length ) {
		static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ';
		$str = '';
		for ( $i = 0; $i < $length; ++$i ) {
			$str .= $chars[mt_rand( 0, 51 )];
		}
		return $str;
	}




	// 各種設定と、フィルターのログの初期値を作成
	private function add_end_of_adblock_cycle() {
		$end_of_adblock_cycle = get_site_option('end_of_adblock_cycle_setting');
		if ( ! $end_of_adblock_cycle ) {
			$rand_str = $this->make_rand_str(8);
			$setting = array(
				'caution_active' => 1, // 警告
				'count_active' => 1, // カウント
				'message' => "<ul><li>このサイトは広告費により運営されています。</li><br /><li>広告ブロックを無効にしてください。</li></ul>",
				'img' => "", // 画像を表示する場合は指定（フルパス）
				'background_color' => "#FFFFFF", // 背景色
				'opacity' => 95, // 背景色の透明度
				'sample_rate' => 1, // カウントのサンプリングレート
				'rand_str' => $rand_str // Adblock Killerなどの対策に生成するランダムな文字列
			);
			update_option( 'end_of_adblock_cycle_setting', $setting );
		}

		$end_of_adblock_cycle_log = get_site_option('end_of_adblock_cycle_log');
		if ( ! $end_of_adblock_cycle_log ) {
			$log = array(
				'adBlockDetected' => 0, // Adblockユーザー
				'adBlockNotDetected' => 0 // Adblockを利用していないユーザー
			);
			update_option( 'end_of_adblock_cycle_log', $log );
		}
	}




	// fuckadblockを元に「クラス」と「ファイル名」を生成したランダムな文字列に変換して保存
	public function create_js() {
		$eoac_setting = get_site_option('end_of_adblock_cycle_setting');
		$filename = plugins_url( '/js/fuckadblock.js', __FILE__ );
		$buff = file_get_contents( $filename );

		// Copyrightの文字列は変換したくないので分離する
		$exp_data = explode( "\n", $buff );
		$count = count( $exp_data );
		for( $i = 0; $i < 7; $i++ ){
			$copyright .= $exp_data[$i] . "\n";
		}
		for( $i = 7; $i < $count; $i++ ){
			$main_code .= $exp_data[$i] . "\n";
		}

		$search = array( 'FuckAdBlock', 'fuckAdBlock' ); // FuckAdBlockではアッパーキャメルケースとローワーキャメルケースで使い分けているので注意
		$replace = array( $eoac_setting["rand_str"], $eoac_setting["rand_str"] . '2' );
		$main_code = str_replace( $search, $replace, $main_code );

		$new_buff = $copyright . $main_code; // 分離して置換したものを結合

		$new_filename = plugin_dir_path( __FILE__ ) . 'js/' . $eoac_setting["rand_str"] . '.js';
		file_put_contents( $new_filename, $new_buff );
	}




	// 生成したJSファイルを削除（停止時に作成したjsファイルを削除する）
	private function remove_js() {
		$eoac_setting = get_site_option('end_of_adblock_cycle_setting');
		$filename = plugin_dir_path( __FILE__ ) . 'js/' . $eoac_setting["rand_str"] . '.js';
		unlink( $filename );
	}




	// 保存した設定とログを削除
	private function remove_end_of_adblock_cycle() {
		delete_option('end_of_adblock_cycle_setting');
		delete_option('end_of_adblock_cycle_log');
	}

}




/**
 * スパム判定と設定画面用クラス
 */
class End_Of_Adblock_Cycle {

	/**
	 * 初期設定
	 */
	public function __construct() {

		// ランダム名のJSファイルが存在するか調べる（アップデート処理対策）
		$setting = get_site_option('end_of_adblock_cycle_setting');
		if ( isset( $setting["rand_str"] ) && ! file_exists( $fa_filename = plugins_url( 'js/' . $setting["rand_str"] . '.js', __FILE__ ) ) ) { // アクティベート時に$setting["rand_str"]が無い状態では実行しない
			$activate = new End_Of_Adblock_Cycle_Activate();
			$activate->create_js();
		}

		// ヘッダーにライブラリ関係のJavaScriptの追加
		add_action( 'wp_head', array( $this, 'insert_custom_js') );

		// ヘッダーにFuckAdBlockの動作を決めるカスタムのJavaScriptの追加
		add_action( 'wp_enqueue_scripts', array( $this, 'fuck_adblock_js') );

		// ajaxを待ち受ける関数の定義（本来の用途ではログインユーザー用はいらないが動作テストで使うので設定しておく）
		add_action( 'wp_ajax_eoac_count', array($this, 'eoac_count') ); // ログインユーザー用
		add_action( 'wp_ajax_nopriv_eoac_count', array($this, 'eoac_count') ); // ゲストユーザー用

		// 設定ページの追加
		add_action( 'admin_menu', array( $this, 'add_eoac_menu') );

		// 設定ページに追加する項目の定義
		add_action( 'admin_init', array( $this, 'register_mysettings' ) );

		// 設定ページ専用のJavaScriptを追加するために定義
		add_action( 'admin_init', array( $this, 'amcharts_js_init' ) );
	}




	/**
	 * ajaxを待ち受ける関数
	 */
	public function eoac_count() {

		$log = get_site_option('end_of_adblock_cycle_log');
		$setting = get_site_option('end_of_adblock_cycle_setting');

		// 値にerrorがある場合は処理停止
		$key = array_search( "error", $setting );
		if ( $key !== false ){
			// wp_die("error");
			return;
		}

		// tokenのチェック 第2引数はnonce生成時と合わせる
		if ( ! wp_verify_nonce( $_POST['token'], 'eoac-token' ) ) {
			die("EOAC: token error!"); // これがレスポンスに渡される
		}

		if ( isset( $_POST['detected'] ) ) {
			$detected = $log['adBlockDetected'] + $setting['sample_rate'];
			$log = array(
				'adBlockDetected' => $detected,
				'adBlockNotDetected' => $log['adBlockNotDetected']
			);
		} else {
			$not_detected = $log['adBlockNotDetected'] + $setting['sample_rate'];
			$log = array(
				'adBlockDetected' => $log['adBlockDetected'],
				'adBlockNotDetected' => $not_detected
			);

		}
		update_option( 'end_of_adblock_cycle_log', $log );

		// die( "eoac: count OK." . var_dump($log) ); // test用
		die( "EOAC: count OK." );
	}




	/**
	 * 設定ページの定義
	 */
	public function register_mysettings() {
		register_setting( 'eoac-group', 'end_of_adblock_cycle_setting', array( $this, 'end_of_adblock_cycle_setting_validation' ) );
	}




	/**
	 * 設定のバリデーション
	 * 
	 * パラメータはWordPressから渡される設定画面の入力値。
	 * 想定外の値の場合、処理を停止、もしくは、errorを入れて返す
	 */
	public function end_of_adblock_cycle_setting_validation( $input ) {

		// 警告の設定（0か1）
		if ( ! ( $input['caution_active'] == 0 || $input['caution_active'] == 1 ) ) {
			$input['caution_active'] = "error";
			wp_die( error ); // チェックボックス形式なので上記以外はありえないため、処理の停止
		}

		// カウントの設定（0か1）
		if ( ! ( $input['count_active'] == 0 || $input['count_active'] == 1 ) ) {
			$input['count_active'] = "error";
			wp_die( error );
		}

		// 画像のURL（htmlか空）
		if ( ! ( filter_var( $input['img'], FILTER_VALIDATE_URL ) &&
			preg_match( '@^https?+://@i', $input['img'] ) ||
			empty( $input['img'] )
		) ) {
			$input['img'] = "error";
		}

		// 塗りつぶしの背景色（ウェブカラー3桁か6桁）
		if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $input['background_color'] ) ) {
			$input['background_color'] = "error";
		}

		// 背景の透明度（0から100）
		if ( ! preg_match( '/^([1-9]?[0-9]|100)$/', $input['opacity'] ) ) {
			$input['opacity'] = "error";
		}

		// サンプリングレート（1以上の整数）
		if ( ! preg_match( '/^([1-9][0-9]*)$/', $input['sample_rate'] ) ) {
			$input['sample_rate'] = "error";
		}

		return $input;
	}




	/**
	 * 設定ページの設定とそこで呼び出すJavaScriptとCSSの呼び出し
	 */
	public function add_eoac_menu() {

		// JavaScriptを呼び出すために$page_hook_suffixに代入している
		$page_hook_suffix = add_options_page(
			'End of Adblock Cycle', // page_title
			'End of Adblock Cycle', // menu_title
			'administrator', // capability
			'end-of-adblock-cycle', // menu_slug
			array( $this, 'display_plugin_admin_page' ) // function
		);

		// このプラグインの設定画面限定でJavaScriptを呼び出すためのハック
		add_action( 'admin_print_scripts-' . $page_hook_suffix, array( $this, 'amcharts_js' ) );
	}




	/**
	 * 設定画面でグラフ表示用のjsとcssを定義（admin_initで定義している）
	 */
    function amcharts_js_init() {
        wp_register_script( 'amcharts-js', plugins_url( '/js/amcharts.js', __FILE__ ) );
        wp_register_script( 'pie-js', plugins_url( '/js/pie.js', __FILE__ ) );
        wp_register_script( 'export-js', plugins_url( '/js/export.js', __FILE__ ) );
        wp_register_style( 'export-css', plugins_url( '/css/export.css', __FILE__ ) ); // 関数名と違ってcssも追加したけど分けるもの面倒だしこのままでいいね
    }




	/**
	 * amcharts_js_initで定義したjsとcssを追加（hook_suffixで呼び出し用）
	 */
	function amcharts_js() {
		wp_enqueue_script( 'amcharts-js' );
		wp_enqueue_script( 'pie-js' );
		wp_enqueue_script( 'export-js' );
		wp_enqueue_style( 'export-css' );
	}




	/**
	 * fuckadblockを追加するための定義
	 */
	public function fuck_adblock_js() {
		$setting = get_site_option('end_of_adblock_cycle_setting');

		// 値にerrorがある場合は処理停止
		$key = array_search( "error", $setting );
		if ( $key !== false ){
			// wp_die("error");
			return;
		}

		$fa_filename = plugins_url( 'js/' . $setting["rand_str"] . '.js', __FILE__ );
		$bu_filename = plugins_url( 'js/jquery.blockUI.js', __FILE__ );
		wp_enqueue_script( $setting["rand_str"], $fa_filename, array( 'jquery' ) );
		wp_enqueue_script( 'jquery.blockUI.js', $bu_filename, array( 'jquery' ) );
	}




	/**
	 * fuckadblockの動作を指定するjsファイルを各投稿に追加
	 */
	public function insert_custom_js() {
		if ( is_page() || is_single() ) {
			$setting = get_site_option('end_of_adblock_cycle_setting');

			// 値にerrorがある場合は処理停止
			$key = array_search( "error", $setting );
			if ( $key !== false ){
				// wp_die("error");
				return;
			}

			if ( empty( $setting["count_active"] ) && empty( $setting["caution_active"] ) ){
				return;
			}
?>

<!-- End of Adblock Cycle -->
<script type="text/javascript">//<![CDATA[
function e_count_d() {
jQuery(function($){
<?php if ( ! empty( $setting["count_active"] ) ) : // カウントがアクティブの場合 ?>
	var num = Math.floor( Math.random() * <?php echo $setting["sample_rate"] ?> ) + 1;
	if( 1 === num ){
		$.ajax({
	        type: 'POST',
	        url: '<?php echo admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'); ?>',
	        data: {
				'action': 'eoac_count',
				'token': '<?php echo wp_create_nonce('eoac-token') ?>',
				'detected': 1
			},
	        success: function( response ){
	            console.log( response );
	        },
			error: function(xhr, textStatus, errorThrown){
				console.log( errorThrown );
			}
	    });
	}
<?php endif; ?>
<?php if ( ! empty( $setting["caution_active"] ) && ! $setting["img"] == "" ) : // 警告がアクティブで画像がセットされている場合 ?>
	var templatePath = "<?php echo $setting["img"]; ?>";
	var displayBox = $('<div id="displayBox"><img src="' + templatePath + '" style="opacity: 0"></div>');
	<?php // bodyの最後に追加（parentを使っていたが2秒遅らせるので変更） ?>
	$("body").after(displayBox);

	<?php // 画像の要素追加が遅れることがあるので2秒遅らせる ?>
	setTimeout(function(){

	$.blockUI({
		<?php if ( isset( $setting["message"] ) && ! $setting["message"] == "" ) : // 画像とメッセージがある場合 ?>
			<?php //通常は追加したdisplayBoxやそのDOMを指定するが画像とメッセージ両方表示するためにトリッキーな表示 ?>
			message: '<div id="displayBox"><img src="' + templatePath + '" style="margin:0 0 15px;">' + '<?php echo $setting["message"]; ?>' + '</div>',
		<?php else : ?>
			message: '<div id="displayBox"><img src="' + templatePath + '"></div>',
		<?php endif; ?>
		css: {
			<?php // メッセージに付くボーダー ?>
			border: 'none',
			<?php // メッセージの背景色 ?>
			background: 'none',
			<?php // 文字色 ?>
			color: '#000',
			<?php // 画像の上辺の表示位置 ?>
			top:  ($(window).height() - $('#displayBox img').height()) /2 + 'px',
			<?php // 画像の左辺の表示位置 ?>
			left: ($(window).width() - $('#displayBox img').width()) /2 + 'px',
			width: $('#displayBox img').width() + 'px' <?php // 画像の幅 ?>
		},
		overlayCSS: {
			backgroundColor: '<?php echo $setting["background_color"]; ?>', <?php // オーバーレイの背景色 ?>
			opacity: <?php echo $setting["opacity"]*0.01; ?>, <?php // オーバーレイの透明度 ?>
		},
		<?php // zindex対策 ?>
		baseZ: 9999
	});

	displayBox.hide();

	}, 2000);
<?php elseif ( ! empty( $setting["caution_active"] ) && ! $setting["message"] == "" ) : // 警告がアクティブで画像は無いがメッセージがある場合 ?>
	$.blockUI({
		message: '<?php echo $setting["message"]; ?>',
		css: {
			<?php // メッセージに付くボーダー ?>
			border: 'none',
			<?php // メッセージの背景色 ?>
			background: 'none',
			<?php // 文字色 ?>
			color: '#000',
		},
		overlayCSS: {
			<?php // オーバーレイの背景色 ?>
			backgroundColor: '<?php echo $setting["background_color"]; ?>',
			<?php // オーバーレイの透明度 ?>
			opacity: <?php echo $setting["opacity"]*0.01; ?>
		},
		<?php // zindex対策 ?>
		baseZ: 9999
	});

<?php elseif ( ! empty( $setting["caution_active"] ) ) : // 警告がアクティブで画像もメッセージも無い場合 ?>
	$.blockUI({
		message: "",
		overlayCSS: {
			<?php // オーバーレイの背景色 ?>
			backgroundColor: '<?php echo $setting["background_color"]; ?>',
			<?php // オーバーレイの透明度 ?>
			opacity: <?php echo $setting["opacity"]*0.01; ?>
		},
		<?php // zindex対策 ?>
		baseZ: 9999
	});
<?php endif; ?>
});
}

<?php if ( ! empty( $setting["count_active"] ) ) : // カウントがアクティブの場合 ?>
function e_count_nd() {
jQuery(function($){
	<?php // ajax処理 ?>
	var num = Math.floor( Math.random() * <?php echo $setting["sample_rate"] ?> ) + 1;
	if( 1 === num ){
		$.ajax({
	        type: 'POST',
	        url: '<?php echo admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'); ?>',
	        data: {
				'action': 'eoac_count',
				'token': '<?php echo wp_create_nonce('eoac-token') ?>'
			},
	        success: function( response ){
	            console.log( response );
	        },
			error: function(xhr, textStatus, errorThrown){
				console.log( errorThrown );
			}
	    });
	}
});
}
<?php endif; ?>

if(typeof <?php echo $setting["rand_str"]; ?>2 === 'undefined') {
    e_count_d();
} else {
    <?php echo $setting["rand_str"]; ?>2.on(true, e_count_d);
    <?php echo $setting["rand_str"]; ?>2.on(false, e_count_nd);
}
//]]></script>

<?php
		} // if ( is_page() || is_single() ) {
	} // public function insert_custom_js() {




	/**
	 * 管理画面の設定に追加されるプラグインの設定
	 * 
	 * 設定を保存するフォームと
	 * AmChartsによるログの表示
	 */
	public function display_plugin_admin_page() {
	?>
	 
	<div class="wrap">

<?php

$options = get_option( 'end_of_adblock_cycle_setting' );

// バリデーションエラー時のメッセージ

// 画像のURLが不正な値の場合
if( $options["img"] == "error" ) {
	add_settings_error(
	    'img-url-error', // エラーのスラッグ
	    'img-url-error', // エラーのコード　<div>のidに割り振られる
	    __('「画像のURL」はフルパスで入力してください。例）http://example.com/img.png', 'end_of_adblock_cycle'), // エラーメッセージ,ローカライゼーションする気ないので第2引数はいらない
	    'error' // メッセージタイプ。error もしくは notice
	);
	settings_errors('img-url-error'); // 引数でエラーのスラッグを指定するとエラーを限定できる
}

// 塗りつぶしの背景色
if( $options["background_color"] == "error" ) {
	add_settings_error(
	    'background_color-error',
	    'background_color-error',
	    __('「塗りつぶしの背景色」はWebカラーを入力してください。例）#FFF, #FFFFFF', 'end_of_adblock_cycle'),
	    'error'
	);
	settings_errors('background_color-error');
}

// 塗りつぶしの背景色
if( $options["opacity"] == "error" ) {
	add_settings_error(
	    'opacity-error',
	    'opacity-error',
	    __('「塗りつぶしの背景色」は0～100の整数値を入力してください。例）95', 'end_of_adblock_cycle'),
	    'error'
	);
	settings_errors('opacity-error');
}

// サンプリングレート
if( $options["sample_rate"] == "error" ) {
	add_settings_error(
	    'sample_rate-error',
	    'sample_rate-error',
	    __('「サンプリングレート」は1以上の整数値を入力してください。例）10, 100', 'end_of_adblock_cycle'),
	    'error'
	);
	settings_errors('sample_rate-error');
}
?>
	 
	<h2>End of Adblock Cycleの設定</h2>
	 
	<form method="post" action="options.php">
	 
<?php
	settings_fields( 'eoac-group' );
	do_settings_sections( 'default' );
?>

	<table class="form-table">
	     <tbody>
	     <tr>
	          <th scope="row"><label for="caution_active">警告の設定</label></th>
	          <td>
	               <label for="caution_active"><input type="hidden" name="end_of_adblock_cycle_setting[caution_active]" value="0"></label>
	               <label><input id="caution_active" type="checkbox" name="end_of_adblock_cycle_setting[caution_active]" size="30" value="1"<?php if( isset($options["caution_active"]) && $options["caution_active"] == 1 ) echo ' checked="checked"'; ?>/>広告ブロックユーザーに対して警告を行う</input></label>
	          </td>
	     </tr>
	     </tbody>
	</table>
	<table class="form-table">
	     <tbody>
	     <tr>
	          <th scope="row"><label for="message">メッセージ<br />（htmlタグ使用可）</label></th>
	          <td>
	               <label for="message">
					<?php
						// htmlの入力に対応したフォーム（値のエスケープを忘れずに）
						wp_editor( esc_html( $options['message'] ), 'message', array(
							'tinymce' => false,
							'quicktags' => false,
							'teeny' => false,
							'wpautop' => false,
							'media_buttons' => false,
							'textarea_name' => 'end_of_adblock_cycle_setting[message]',
							'textarea_rows' => 3
						) );
					?>
					</label>
	          </td>
	     </tr>
	     </tbody>
	</table>
	<table class="form-table">
	     <tbody>
	     <tr>
	          <th scope="row"><label for="img">画像のURL</label></th>
	          <td>
	               <label for="img"><input id="img" type="text" name="end_of_adblock_cycle_setting[img]" size="50" placeholder="http://example.com/img.png" value="<?php if ( esc_html( $options['img'] ) ) echo esc_html( $options['img'] ); ?>" /></input></label>
	          </td>
	     </tr>
	     </tbody>
	</table>
	<table class="form-table">
	     <tbody>
	     <tr>
	          <th scope="row"><label for="background_color">塗りつぶしの背景色<br />（ウェブカラー）</label></th>
	          <td>
	               <label for="background_color"><input id="background_color" type="text" name="end_of_adblock_cycle_setting[background_color]" size="50" placeholder="#FFFFFF" value="<?php if ( esc_html( $options['background_color'] ) ) echo esc_html( $options['background_color'] ) ?>" /></input></label>
	          </td>
	     </tr>
	     </tbody>
	</table>
	<table class="form-table">
	     <tbody>
	     <tr>
	          <th scope="row"><label for="opacity">背景の透明度<br />（0～100）</label></th>
	          <td>
	               <label for="opacity"><input id="opacity" type="text" name="end_of_adblock_cycle_setting[opacity]" size="50" placeholder="95" value="<?php if ( esc_html( $options['opacity'] ) ) echo esc_html( $options['opacity'] ); ?>" /></input></label>
	          </td>
	     </tr>
	     </tbody>
	</table>
	<hr />
	<table class="form-table">
	     <tbody>
	     <tr>
	          <th scope="row"><label for="count_active">カウントの設定</label></th>
	          <td>
	               <label for="count_active"><input type="hidden" name="end_of_adblock_cycle_setting[count_active]" value="0"></label>
	               <label><input id="count_active" type="checkbox" name="end_of_adblock_cycle_setting[count_active]" size="30" value="1"<?php if( isset($options["count_active"]) && $options["count_active"] == 1 ) echo ' checked="checked"'; ?>/>広告ブロックユーザーをカウントする</input></label>
	          </td>
	     </tr>
	     </tbody>
	</table>
	<table class="form-table">
	     <tbody>
	     <tr>
	          <th scope="row"><label for="sample_rate">サンプリングレート<br />（推奨値：10～100）</label></th>
	          <td>
	               <label for="sample_rate"><input id="sample_rate" type="text" name="end_of_adblock_cycle_setting[sample_rate]" size="50" placeholder="95" value="<?php if ( esc_html( $options['sample_rate'] ) ) echo esc_html( $options['sample_rate'] ); ?>" /></input></label>
	          </td>
	     </tr>
	     </tbody>
	</table>

	<input type="hidden" name="end_of_adblock_cycle_setting[rand_str]" value="<?php echo esc_html( $options['rand_str'] ); ?>">

	<?php submit_button(); // 送信ボタン ?>

	</form>

	<?php
		$log = get_option( 'end_of_adblock_cycle_log' );
	?>

	<hr />

	<h3>End of Adblock Cycleのログ</h3>

	<script>
	AmCharts.makeChart("chartdiv", {
	"type": "pie", // グラフの種類（pieは円グラフ）
	"dataProvider":[{ // グラフのデータ http://docs.amcharts.com/3/javascriptcharts/AmChart#dataProvider
		"title": "広告ブロックが有効なユーザー",
		"value": <?php echo $log["adBlockDetected"]; ?>
	}, {
		"title": "一般ユーザー",
		"value": <?php echo $log["adBlockNotDetected"]; ?>
	}],
	"titleField": "title", // タイトルのフィールドを指定
	"valueField": "value", // 値のフィールドを指定
	"labelRadius": 20, // 円グラフからラベルまでの距離（罫線の長さ）
	"radius": "40%", // 円のサイズデフォルトだとはみ出すので注意 default:90
	"innerRadius": "65%", // 円の内部の空白（ドーナツ状になる） default:0
	"labelText": "[[title]]", // ラベル
	"colors": [ // スライスに使われる色（項目が色より多い場合はここに無い色が使われる）
		"#adcfee",
		"#d6d6d6"
	],
	"export": { // ダウンロードの許可 専用のプラグインを導入する必要あり　http://www.amcharts.com/tutorials/intro-exporting-charts/
		"enabled": true, // ダウンロードを有効に
		"libs": { // ライブラリを指定（これがないとpng等の作成はできない）
			"path": "<?php echo plugins_url( '/js/libs/', __FILE__ ) ?>"
		}
	},
	});

	</script>

	<div id="chartdiv" style="width:100%; height:500px;"></div>

	</div><!-- .wrap -->
	<?php
	}




} // end class




// インスタンスの作成（コンストラクタの実行）
$End_Of_Adblock_Cycle = new End_Of_Adblock_Cycle();
