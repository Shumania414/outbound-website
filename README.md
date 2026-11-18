# out-bounder.com

OUTBOUND K.K. のウェブサイト（https://www.out-bounder.com）。

- `site/` の中身が、さくらのレンタルサーバ `/home/outbound/www/` にそのまま反映されます。
- さくらの CRON が数分おきにこのリポジトリを取り込み、`main` に新しい変更があれば `site/` を本番へコピーします。
- コピーは上書きのみで、本番にしかないファイル（`withbluedmc/` など）は消しません。
- 元に戻すときは、戻したいコミットを `git revert` して `main` に反映すると、次の取り込みで本番も戻ります。
