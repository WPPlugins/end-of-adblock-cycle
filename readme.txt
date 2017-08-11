=== End of Adblock Cycle ===
Contributors: oxynotes
Donate link: https://wordpress.org/plugins/end-of-adblock-cycle/
Tags: Adblock, Adblock Plus, uBlock, Crystal, Anti-Adblock Killer, Disable Anti-Adblock, Warning, Count, blockUI, FuckAdBlock
Requires at least: 4.3.1
Tested up to: 4.4
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adblock等で広告を非表示にしてるユーザーへの警告や、ユーザー数の把握

== Description ==

Adblock等の広告を非表示にするソフトを導入しているユーザーに対して警告を行います。警告の方法は画像やメッセージを表示したり、特定の色で画面を塗りつぶして行います。

また、Adblock等はGoogle Analyticsのコードを停止するためユーザー数の把握が困難でしたが、このプラグインではAdblockユーザーをカウントすることができます。
一般ユーザーとAdblockユーザーの割合を設定画面で表示することができます。

警告を無効にするAnti-Adblock KillerやDisable Anti-Adblock等を導入していても警告やカウントを行います。

設定は以下のとおりです。

= 警告の設定 =

この項目にチェックを入れると、広告ブロックユーザーに対して警告を行います。

= メッセージ =

警告に使用するメッセージを入力します。（htmlタグ使用可）

= 画像のURL =

警告に使用する画像のURLを入力します。画像のURLはフルパスで入力してください。

= 塗りつぶしの背景色 =

ページを塗りつぶす色を入力します。色の指定はWebカラーで入力してください。（例：#FFF, #FFFFFF）

= 背景の透明度 =

「塗りつぶしの背景色」に指定した色の透明度を指定します。入力は0～100の整数を入力してください。

= カウントの設定 =

この項目にチェックを入れると、広告ブロックユーザーをカウントします。

= サンプリングレート =

サンプリングレートは、大量のアクセスがあるWebサイト向けの負荷対策です。

デフォルトでは全てのPVをカウントし、「1」をプラスします。
サンプリングレートを「10」にすると、10PV時にカウントを行います。この時のカウントは「10」プラスされます。

数字を増やせばサーバへの負荷は減りますが、正確性も損なわれます。

= 注意点 =

カウントは悪意のある操作の対策としてWordPress Nonceを利用しています。
nonceの有効期間はデフォルトで1日なので、ページキャッシュやリバースプロキシ等で1日以上経過すると正しくカウントできません。
キャッシュの期間を短くするか、nonceの有効期限を環境に合わせて設定してください。

また、JavaScriptが無効な環境では動作しません。

詳しい使い方や解説は[作者の解説ページ](http://oxynotes.com/?p=9707)をご覧ください。

= Thanks =

以下のライブラリを利用して作成されています。

1. [FuckAdBlock](https://github.com/sitexw/FuckAdBlock)
1. [jQuery blockUI plugin](http://malsup.com/jquery/block/)
1. [amCharts](http://www.amcharts.com/)

== Installation ==

1. プラグインの新規追加ボタンをクリックして、検索窓に「End of Adblock Cycle」と入力して「今すぐインストール」をクリックします。
1. もしくはこのページのzipファイルをダウンロードして解凍したフォルダを`/wp-content/plugins/`ディレクトリに保存します。
1. 設定画面のプラグインで **End of Adblock Cycle** を有効にしてください。

== Frequently asked questions ==

-

== Screenshots ==

1. Option page.

2. 画像とメッセージ有効時の警告表示。

== Changelog ==

1.2
jsファイルが存在しない場合に作成するように変更。警告・カウントを有効・無効、選択できるように変更。

1.1
jQueryの呼び出し方変更。一部テーマ用のzindex対策。

1.0
初めのバージョン。


== Upgrade notice ==

-