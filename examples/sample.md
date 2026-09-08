# 基本的なMarkdown

[TOC]

これは通常の段落です。**強調**、*斜体*、`インラインコード`を含みます。

- 箇条書きの項目1
- 箇条書きの項目2

## note、aside、details

::: note info "参考情報"
これは通常の補足です。[リンク](https://example.com/note)や **強調** を含められます。

- 補足内のリスト
- 2番目の項目
:::

::: note warn "注意"
設定を変更する前に、[現在値の確認手順](https://example.com/check)を確認してください。
:::

::: note alert "重要な警告"
この操作は取り消せません。Markdown上の `alert` は表示種別であり、
ARIAの `role="alert"` には変換しません。[操作上の注意](https://example.com/caution)も参照してください。
:::

::: aside "関連情報"
本文から少し外れる関連情報です。HTMLの `aside` 要素になります。
:::

::: details "詳しい説明"
この部分は `details` と `summary` になり、開閉できます。

```php
echo 'details内のコードブロック';
```
:::

## 通常の表

| 項目 | 状態 |
| --- | --- |
| 通常セル | 有効 |
| 2行目 | 確認済み |

## 行見出し付きの表

| 曜日 | 開館時刻 | 閉館時刻 |
| --- | --- | --- |
| 月曜日: | 9:00 | 17:00 |
| 火曜日: | 10:00 | 18:00 |

先頭列の本文セル末尾に「:」を付けています。列見出しには
`scope="col"`、行見出しには `scope="row"` が付くかを確認します。

## ヘッダー行のscope="row"

| scope row:| scope col |
|-----------|-----------|
| Row 1    :| Row2      |

ヘッダー行の1行1列目と、本文行の1列目が `scope="row"` になる例です。
`scope="row"` のセルは、背景色と太い左罫線で区別しています。

## 前置caption付きの表

*2026年9月時点の地域別人数*
| 地域 | 人数 |
| --- | ---: |
| 東 | 12 |
| 西 | 8 |

表の直前に空行を入れず、強調だけの段落を置く新しいcaption記法です。
League標準Extensionだけでは、強調段落と表として読めることを確認します。

## 空行でcaptionにしない表

*この強調文はcaptionではありません*

| 項目 | 値 |
| --- | --- |
| A | 10 |

強調段落と表の間に空行があるため、通常の強調段落のまま残ります。

## 属性付きtableとcaption

| 種別 | 値 |
| --- | --- |
| A | 10 |
| B | 20 |
|: 属性指定を試した表のキャプション
{#summary-table .comparison-table}

Markdown Extra形式のブロック属性を表の直後に置いた境界例です。tableに
`id`と`class`が付き、captionがtableの先頭に入ることを確認します。
この表では下位互換確認のため、表内の `|: caption` 記法を使っています。

## figureとfigcaption

![青い四角のサンプル画像](<files/sample-image.svg?variant=(blue)> "画像の説明"){#figure-sample .sample-image}
*青い四角を示す[詳しい説明](https://example.com/figure)と **強調** と `コード`*

画像側のtitle・属性・括弧を含むURLと、figcaption内のリンク・強調・コードが
それぞれ変換されることを確認します。

## リンクとファイル容量

- [通常の外部リンク](https://example.com/)
- [ローカルの小さな文書](/files/download.txt)
- ![通常のMarkdown画像](files/sample-image.svg)
- [画像ファイルへの通常リンク](/files/sample-image.svg)
- [「/」から始まるURL](/manual/start)

Markdown画像の直後にはファイル形式・容量が付かないことを確認します。
画像ファイルへの通常リンクは、画像拡張子の境界例としてSVGを使っています。

## コードブロック内のfigure記法

次の記法は表示例であり、figureへ変換されないことを期待する境界例です。

```markdown
![コード例の画像](files/sample-image.svg)
*コード例のキャプション*
```

## URLとリンク属性の境界例

- [括弧、クエリ、フラグメントを含むURL](<https://example.com/a_(b)?first=1&second=2#result>)
- [title付きリンク](https://example.com/document "リンクの説明")
- [属性付きリンク](https://example.com/){.external-link lang=ja}
- [強調を含むリンク **重要**](https://example.com/emphasis)

これらは、URLの括弧や `&` のエスケープ、title、Markdown Extra形式の
リンク属性、リンク文字列内のインライン記法を確認するための入力です。
