# Wiki-CGroup-Crawler [![Build Status](https://travis-ci.org/jfcherng/Wiki-CGroup-Crawler.svg?branch=master)](https://travis-ci.org/jfcherng/Wiki-CGroup-Crawler)

此腳本用於抓取維基百科的公共轉換組詞庫，並將結果儲存為外部檔案。

- https://zh.wikipedia.org/wiki/Category:公共轉換組模板
- https://zh.wikipedia.org/wiki/Category:公共转换组模块


# 安裝

`$ composer require jfcherng/wiki-cgroup-crawler`


# 範例

詳見 `demo.php` 。執行 `$ php demo.php` 後，結果將存放於 `results` 資料夾。

以下是 `results/Wow.json` （[魔獸世界 公共轉換組](https://zh.wikipedia.org/wiki/%E6%A8%A1%E5%9D%97:CGroup/Wow)） 的部分內容：

```javascript
[
    {
        "zh-cn": "暗夜精灵",
        "zh-tw": "夜精靈",
        "original": "Nightelf"
    },
    {
        "zh-cn": "亡灵",
        "zh-tw": "不死族",
        "original": "Undead"
    },
    {
        "zh-cn": "巨魔",
        "zh-tw": "食人妖",
        "original": "Troll"
    },
    {
        "zh-cn": "侏儒",
        "zh-tw": "地精",
        "original": "Gnome"
    },
    // ... 以下省略
]
```


Supporters <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ATXYY9Y78EQ3Y" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" /></a>
==========

Thank you guys for sending me some cups of coffee.
