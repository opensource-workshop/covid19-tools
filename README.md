# covid19-tools
新型コロナの感染拡大に役立つツールを公開していきます。

第一弾：COCOAログビューア  
厚生労働省の接触確認アプリCOCOAのログは多くの情報を持っています。このログと自分の行動履歴をもとに、自分で自分の行動結果を確認できるようにすることで、多くの人ができるだけ安全に行動ができ、健康と経済活動を両立できるように願って、このシステムを開発ました。
  
第二談：感染増減率モニター  
感染者数だけではなく、感染者数の前週比率を観測することで、少し先の状況を予測することができます。  
もちろん、予測できる以上、感染防御にも役立つと考えています。  
そのために、感染者数の前週比率をグラフでわかりやすく表示します。  
  
「covid19-tools」リポジトリは、Connect-CMS の標準パッケージには含まれない、Covid19関係のオプション・プラグインを格納するためのリポジトリです。  
Connect-CMS の標準パッケージは以下を参照してください。  
https://github.com/opensource-workshop/connect-cms  
  
データベースの migration は以下のコマンドで行います。  
php artisan migrate --path=database/migrations_option  
  
サービスサイトは以下  
https://cocoalog.connect-cms.jp/

