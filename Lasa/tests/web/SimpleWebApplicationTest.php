// <?php
// /*
//  * SimpleWebApplicationTest.php
//  */

// class SimpleWebApplicationTest extends PHPUnit_Framework_TestCase{
	
// 	function testLoader(){
		
// 		//各URLのテストが行える
// 		Applicatoin::make();
// 		//はApplicationLoaderで登録したものが使われるって感じ
		
// // 		$testLoader = new ApplicationLoader();
// // 		$testLoader->bind(Applicatoin::class, function(){
// // 			return new TestApplication();
// // 		});
		
// 		$loader = new ApplicationLoader();
		
// 		//Applicationの作成
// 		$loader->bind(Application::class, function(){
// 			return new Application();
// 		});
// 		$loader->controller(function(ApplicationLoader $app, $request){
// 			//自動的にパスを作って読み込むが標準動作
// 			//どうするかだね
// 		});
// 		$loader->enviroment(function(ApplicationLoader $loader, $request){
			
// 		});
// 		$loader->module(function(ApplicationLoader $app, $module){
// 			//自動的にパスを作って読み込むが標準動作
// 			//どうするかだね
// 		});
		
// 		//通常の実行はこんな感じ
// 		$loader->execute($action_name);
		
// 		$app = $loader->getApplication();
// 		$app->setQuery([
// 			"User" => [
// 				"name" => "name",
// 				"password" => "password0",
// 				"password_confirm" => "password0"
// 			]
// 		]);
// 		$app->execute($arguments);
		
// 		//最小のloaderはこんな感じ
// 		//ディレクトリ構造
// 		$loader = new ApplicationLoader();
// 		$loader->run($action_name);
		
// 		$module->getApplication()->environment();
		
// 		//Applicationとは
// 		//1.リクエストからアクションを作成し、2.コントローラーに紐付け実行し3.viewを表示する
// 		//2-1.リクエストからフィルターを起動する
// 		//ものである
		
// 		//Application(webapplication)は状態がある
		
// 		//action
// 		//URLなどから作成される。コントローラーに紐づく
// 		//「aaa/bbb/ccc」みたいな形で識別される
		
// 		//認証済みとそれ以外で処理を分ける場合はこんな感じ
// 		$loader->controller(function(ApplicationLoader $loader, $request){
// 			//自動的にパスを作って読み込むが標準動作
// 			if($app->make("auth")->check()){
// 				return $loader->loadControllerFromPath("hogehoge/nologin/");
// 			}
			
// 			return $loader->loadControllerFromPath("hogehoge/admin/");
			
// 		});
		
// 	}
	
// }
return;
// ?>
<!-- ApplicationLoader -->
<!-- controller -->
<!-- 	リクエストからコントローラーを決める -->
<!-- プラグインでurlの構造を最適化する -->
<!-- 	例）.htmlで終わるとか、/とか -->
<!-- 	・標準は「/」で終わらない -->
<!-- 	・拡張子を付けない -->
<!-- 	・拡張子がある場合は引数として渡す -->
<!-- directory(app) -->
<!-- 	ディレクトリの構造を定義する -->
<!-- 	enviroment/	環境 -->
<!-- 	configure/	設定 -->
<!-- 		assets.php -->
<!-- 		controller.php -->
<!-- 		module.php -->
<!-- 		log.php -->
<!-- 	controller/	コントローラー -->
<!-- 	module/	モジュール -->
<!-- 	log/	ログ -->
<!-- 	auth/	認証 -->
<!-- 	src/	自作クラス -->
<!-- 	view/	ビュー -->
<!-- 	test/	テスト -->
<!-- 	tmp/	キャッシュ用のディレクトリ -->
<!-- 	assets/ -->
<!-- 		css/ -->
<!-- 		js/ -->
<!-- 	public/	公開用ディレクトリ -->
<!-- 	loader.php -->

<!-- vendor/bin/lasa assets -->

<!-- loader.phpはっていう形になっている -->
<!-- return function(\lasa\web\ApplicationLoader $loader){ -->
<!-- 	//環境をどうするか -->
<!-- 	//標準の振る舞いはLASA_ENVでの読み込み -->
<!-- 	$loader->enviroment(function($loader){ -->
<!-- 		if (getenv('LASA_ENV')) { -->
<!-- 			return getenv('LASA_ENV'); -->
<!-- 		} -->
		
<!-- 		return [ -->
<!-- 			"production" => "hogehoge.production.com", -->
<!-- 			"developing" => "hogehoge-stg.production.com", -->
<!-- 		]; -->
<!-- 	}); -->
	
<!-- 	$loader->controller(function(){ -->
<!-- 		//この部分は環境の方に書いても良い -->
<!-- 	}); -->
<!-- }; -->

<!-- index.phpはこんな感じ -->
<!-- require "vendor/autoloader.php"; -->
<!-- \lasa\web\ApplicationLoader::load("app/tmp/loader.php")->execute(); -->

<!-- //自己を書き換えるというのも有りかもしれない -->
