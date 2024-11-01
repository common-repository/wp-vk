=== WP VK-付费内容插件（付费阅读/资料/工具软件资源管理） ===
Contributors: wbolt,mrkwong
Donate link: https://www.wbolt.com/
Tags: WeChat Pay, xunhupay, Alipay, PAYJS
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.4.3
License: GNU General Public License v2.0 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP VK，中文名称为付费内容插件。近几年付费内容开始在中国风靡起来，一些公众号及站长开始以付费模式经营有价值的文章、资料文档及工具软件资源等。但站长要在网站博客实现付费内容，需要实现支付接口的对接、付费内容部分加密及订单管理等，而WordPress默认功能又无法提供支持。
为此闪电博，开发了一款专属于WordPress的付费内容插件。

== Description ==

WP VK付费内容插件支持站长自主配置支付接口（包括微信支付/支付宝官方支付API和第三方支付接口-虎皮椒和PAYJS）；对部分需要付费的文字、下载等内容执行加密，需用户付费解锁后才能查看。

### 1.插件设置

支持站长对付费内容前端外观进行自定义配置，包括：
* **付费内容提示文字自定义**；
* **付费内容图标颜色及大小自定义**；
* **付费说明文字大小及颜色样式自定义**；
* **自定义CSS样式**。

### 2.订单管理
支持站长对付费内容订单进行管理，包括：
* **订单筛选功能**-支持按付费状态、支付方式、下单时间及用户名或订单号进行订单筛选；
* **订单列表功能**-支持查看订单号、订单日期、订单对应文章、订单金额、用户名/邮箱、支付方式及支付状态等信息。

### 3.支付管理
支持对付费内容支付方式进行配置，包括：
* **第三方接口-虎皮椒**-支持使用虎皮椒提供的支付宝和微信支付接口（个人可申请）作为付费内容的支付方式；
* **第三方接口-PAYJS**-支持使用PAYJS提供的微信支付接口（个人可申请）作为付费内容的支付方式；
* **官方接口**-支持支付宝和微信官方提供的支付接口作为付费内容的支付方式。
无论是第三方接口还是官方接口，均可以同时选择支付宝和微信支付作为付费内容支付方式，但站长务必注意的是，官方接口一般需要企业身份申请；第三方支付接口-虎皮椒则可以个人申请。

### 4.其他功能
付费内容插件还支持配置付费内容小工具、客户端付费内容订单列表页面。
* **付费内容小工具**-支持站长通过 '外观'-'小工具'，添加 '付费内容' 边栏小工具，以方便网站注册用户快速找到已购买的付费内容；
* **客户端付费内容订单页面**-安装插件后，插件将会默认生成一个付费内容订单列表页面（类似于用户中心），但该页面主要用于展示注册用户已购买的付费内容列表。

### 5.付费下载支持
付费内容插件兼容<a href="https://www.wbolt.com/plugins/dip?utm_source=wp&utm_medium=link&utm_campaign=vk" rel="friend" title="WordPress下载插件">WordPress下载插件</a>，也就是说，站长可以利用这两个插件来实现WordPress付费下载。

== 其他WP插件 ==

WP VK是一款专门为WordPress开发的<a href="https://www.wbolt.com/plugins/wp-vk?utm_source=wp&utm_medium=link&utm_campaign=wpvk" rel="friend" title="WP付费内容插件">付费内容插件插件</a>. 插件支持微信支付及支付宝官方API接口，第三方支付接口-虎皮椒和PAYJS；支持站长自定义需要付费内容的文章内容；支持站长管理付费内容订单等。

闪电博（<a href='https://www.wbolt.com/?utm_source=wp&utm_medium=link&utm_campaign=wpvk' rel='friend' title='闪电博官网'>wbolt.com</a>）专注于原创<a href='https://www.wbolt.com/themes' rel='friend' title='WordPress主题'>WordPress主题</a>和<a href='https://www.wbolt.com/plugins' rel='friend' title='WordPress插件'>WordPress插件</a>开发，为中文博客提供更多优质和符合国内需求的主题和插件。此外我们也会分享WordPress相关技巧和教程。

除了付费内容插件插件外，目前我们还开发了以下WordPress插件：

- [多合一搜索自动推送管理插件-历史下载安装数200,000+](https://wordpress.org/plugins/baidu-submit-link/)
- [热门关键词推荐插件-最佳关键词布局插件](https://wordpress.org/plugins/smart-keywords-tool/)
- [Smart SEO Tool-高效便捷的WP搜索引擎优化插件](https://wordpress.org/plugins/smart-seo-tool/)
- [Spider Analyser – WordPress搜索引擎蜘蛛分析插件](https://wordpress.org/plugins/spider-analyser/)
- [WP资源下载管理-WordPress资源下载插件](https://wordpress.org/plugins/download-info-page/)
- [IMGspider-轻量外链图片采集插件](https://wordpress.org/plugins/imgspider/)
- [MagicPost – WordPress文章管理功能增强插件](https://wordpress.org/plugins/magicpost/)
- [Online Contact Widget-多合一在线客服插件](https://wordpress.org/plugins/online-contact-widget/)
- 更多主题和插件，请访问<a href="https://www.wbolt.com/?utm_source=wp&utm_medium=link&utm_campaign=wpvk" rel="friend" title="闪电博官网">wbolt.com</a>!

如果你在WordPress主题和插件上有更多的需求，也希望您可以向我们提出意见建议，我们将会记录下来并根据实际情况，推出更多符合大家需求的主题和插件。

== WordPress资源 ==

由于我们是WordPress重度爱好者，在WordPress主题插件开发之余，我们还独立开发了一系列的在线工具及分享大量的WordPress教程，供国内的WordPress粉丝和站长使用和学习，其中包括：

**<a href="https://www.wbolt.com/learn?utm_source=wp&utm_medium=link&utm_campaign=wpvk" target="_blank">1. Wordpress学院:</a>** 这里将整合全面的WordPress知识和教程，帮助您深入了解WordPress的方方面面，包括基础、开发、优化、电商及SEO等。WordPress大师之路，从这里开始。

**<a href="https://www.wbolt.com/tools/keyword-finder?utm_source=wp&utm_medium=link&utm_campaign=wpvk" target="_blank">2. 关键词查找工具:</a>** 选择符合搜索用户需求的关键词进行内容编辑，更有机会获得更好的搜索引擎排名及自然流量。使用我们的关键词查找工具，以获取主流搜索引擎推荐关键词。

**<a href="https://www.wbolt.com/tools/wp-fixer?utm_source=wp&utm_medium=link&utm_campaign=wpvk">3. WOrdPress错误查找:</a>** 我们搜集了大部分WordPress最为常见的错误及对应的解决方案。您只需要在下方输入所遭遇的错误关键词或错误码，即可找到对应的处理办法。

**<a href="https://www.wbolt.com/tools/seo-toolbox?utm_source=wp&utm_medium=link&utm_campaign=wpvk">4. SEO工具箱:</a>** 收集整理国内外诸如链接建设、关键词研究、内容优化等不同类型的SEO工具。善用工具，往往可以达到事半功倍的效果。

**<a href="https://www.wbolt.com/tools/seo-topic?utm_source=wp&utm_medium=link&utm_campaign=wpvk">5. SEO优化中心:</a>** 无论您是 SEO 初学者，还是想学习高级SEO 策略，这都是您的 SEO 知识中心。

**<a href="https://www.wbolt.com/tools/spider-tool?utm_source=wp&utm_medium=link&utm_campaign=wpvk">6. 蜘蛛查询工具:</a>** 网站每日都可能会有大量的蜘蛛爬虫访问，或者搜索引擎爬虫，或者安全扫描，或者SEO检测……满目琳琅。借助我们的蜘蛛爬虫检测工具，让一切假蜘蛛爬虫无处遁形！

**<a href="https://www.wbolt.com/tools/wp-codex?utm_source=wp&utm_medium=link&utm_campaign=wpvk">7. WP开发宝典:</a>** WordPress作为全球市场份额最大CMS，也为众多企业官网、个人博客及电商网站的首选。使用我们的开发宝典，快速了解其函数、过滤器及动作等作用和写法。

**<a href="https://www.wbolt.com/tools/robots-tester?utm_source=wp&utm_medium=link&utm_campaign=wpvk">8. robots.txt测试工具:</a>** 标准规范的robots.txt能够正确指引搜索引擎蜘蛛爬取网站内容。反之，可能让蜘蛛晕头转向。借助我们的robots.txt检测工具，校正您所写的规则。

**<a href="https://www.wbolt.com/tools/theme-detector?utm_source=wp&utm_medium=link&utm_campaign=wpvk">9. WordPress主题检测器:</a>** 有时候，看到一个您为之着迷的WordPress网站。甚是想知道它背后的主题。查看源代码定可以找到蛛丝马迹，又或者使用我们的小工具，一键查明。

== Installation ==

方式1：在线安装(推荐)
1. 进入WordPress仪表盘，点击`插件-安装插件`，关键词搜索`付费内容插件`，找搜索结果中找到`付费内容`插件，点击`现在安装`；
2. 安装完毕后，启用`付费内容`插件.
3. 通过`付费内容`->`插件设置` 进行插件设置；通过`付费内容`->`支付管理`进行支付设置。 
4. 如果需要配置`付费内容` 边栏入口，可以通过'外观'->`付费内容'，拖拽至边栏即可。
5. 发布付费内容文章时，点击'付费内容'按钮即可将分割线以下内容设置为付费加密内容。

方式2：上传安装

FTP上传安装
1. 解压插件压缩包`wp-vk.zip`，将解压获得文件夹上传至wordpress安装目录下的 `/wp-content/plugins/` 目录.
2. 访问WordPress仪表盘，进入“插件”-“已安装插件”，在插件列表中找到“付费内容”插件，点击“启用”.
3. 通过`付费内容`->`插件设置` 进行插件设置；通过`付费内容`->`支付管理`进行支付设置。 
4. 如果需要配置`付费内容` 边栏入口，可以通过'外观'->`付费内容'，拖拽至边栏即可。
5. 发布付费内容文章时，点击'付费内容'按钮即可将分割线以下内容设置为付费加密内容。

仪表盘上传安装
1. 进入WordPress仪表盘，点击`插件-安装插件`；
2. 点击界面左上方的`上传按钮`，选择本地提前下载好的插件压缩包`wp-vk.zip`，点击`现在安装`；
3. 安装完毕后，启用`付费内容插件`插件；
4. 通过`付费内容`->`插件设置` 进行插件设置；通过`付费内容`->`支付管理`进行支付设置。 
5. 如果需要配置`付费内容` 边栏入口，可以通过'外观'->`付费内容'，拖拽至边栏即可。
6. 发布付费内容文章时，点击'付费内容'按钮即可将分割线以下内容设置为付费加密内容。

关于本插件，你可以通过阅读<a href="https://www.wbolt.com/vk-plugin-documentation.html?utm_source=wp&utm_medium=link&utm_campaign=wpvk" rel="friend" title="插件教程">付费内容插件插件教程</a>学习了解插件安装、设置等详细内容。

== Frequently Asked Questions ==

= 为什么设置了付费还是直接可见内容？ =
如果当前登录用户为管理员，是直接可见付费内容。如需要测试付费内容可见性，建议另开一个浏览器查阅。

= 为什么古腾堡编辑器下无法设置价格？ =
古腾堡编辑器下，设置付费价格，需要在付费内容前，插入“付费内容设置”区块。您可以在编辑器下添加区块，然后搜索“付费内容”，插入付费内容设置区块，然后输入价格，确定即可。

= 网站应该选择哪种支付接口？ =
（1）企业备案网站，建议使用微信支付或者支付宝官方支付接口；
（2）若非企业备案网站，则建议选择虎皮椒或者PAYJS第三方支付接口：其中虎皮椒支付接口，除了微信支付和支付宝按笔收取的手续费外，还需支付虎皮椒接口的开通费用（每个接口88元）及每笔交易1%通道费；PAYJS支付接口，默认仅开通微信支付接口，PAYJS收取开通费用为300元及按笔收取1.76%手续费，微信支付则按官方标准每笔收取手续费。

= 为什么我的PAYJS没有支付宝权限？ =
新申请开通账户暂时没有支付宝接口权限，需要同时满足过去40天交易日达30天、日均交易额100元以上 、日均交易10笔以上、日均支付用户10人以上四个条件才可开通。更多信息，建议联系PAYJS官方。

= 付费内容插件支持什么支付方式？ =
支持支付宝或者微信，可以选择官方接口或者虎皮椒、PAYJS第三方支付接口。

= 该插件是否支持免登录支付？ =
不支持，因为需要记录客户付费内容购买记录。

= 用户是否可以查看过往购买的付费内容？= 
可以。不过站长需要配置'付费内容'小工具，以便于用户通过边栏快速进入付费内容订单列表页面。又或者站长通过配置菜单等方式，提供付费内容列表页面入口。

== Screenshots ==

1. 付费内容展示截图.
2. 付费内容插件微信支付界面截图.
3. 付费内容插件插件设置界面截图.
4. 付费内容插件支付配置界面截图.
5. 付费内容插件订单管理插件截图

== Changelog ==

= 1.4.3 =
* 修复更换域名插件无法使用的问题；
* 变更scss本地库路径。

= 1.4.2 =
* 修复价格保存丢失问题。

= 1.4.1 =
* 优化部分PHP文件代码；
* 引入公共Vue库。

= 1.4.0 =
* 修正古腾堡编辑器设置价格失效的问题。
* 修正一些跳转设置界面的链接异常问题。
* 优化古腾堡编辑器中模块方法及逻辑。
* 优化前端加载插件js细节。
* 基于代码规范、安全及性能进一步优化PHP文件。

= 1.3.7 =
* 修复虎皮椒支付免登陆购买Uncaught Error报错。

= 1.3.6 =
*更新虎皮椒API接口。

= 1.3.5 =
* 新增会员中心支持，支持通过新的会员中心查看订单信息；
* 支持通过在线客服插件前端组件快速进入我的订单；
* 优化暗黑模式下锁定内容位置的样式细节。

= 1.3.4 =
* 增加虎皮椒/迅虎支付网关地址可配置；
* 插件增加csrf安全防护。

= 1.3.3 =
* 新增第三方支付（迅虎API）；
* 新增免登陆标识码设置支持；
* 优化订单管理交互体验；
* 优化资源管理交互体验；
* 自定义样式：增加暗黑模式支持，增加遮盖层颜色可配置;
* 优化前端资源引入逻辑，修正非详情页js报错的问题；
* 修正最新古腾堡设置价格位置样式错位问题。

= 1.3.2 =
* 新增付费资源管理模块；
* 优化订单管理列表样式；
* 其他已知问题修复及体验优化。

= 1.3.1 =
* 优化古腾堡模式下交互；
* 后台管理一些优化: 外观自定义交互调整；应用位置（文章类型）对古腾堡的支持；
* 优化购买成功后返回文章，有时不刷新的问题；
* 修复古腾堡默认价格保存异常的问题。

= 1.3.0 =
* 兼容古腾堡编辑器；
* 插件后台管理更换为vue；
* 新增不同类型文章及页面支持。

= 1.2.10 =
* 解决免登录支付与微信官方支付接口参数冲突问题。

= 1.2.9 =
* 优化Payjs的H5支付为点击跳转支付；
* 解决Windows服务器无法正常使用支付问题；
* 移除微信官方支付api ssl验证；
* 插件设置入口链接调整。

= 1.2.8 =
* 增加插件说明文档链接入口；
* 兼容WordPress 5.7；
* 解决下载资源管理插件兼容性问题；
* 优化插件资源推送版块界面展示；
* 优化插件独立页面URL生成逻辑。

= 1.2.7 =
* 新增微信支付和支付宝官方API接口手机端呼出APP支付支持；
* 新增Payjs支付接口手机端呼出微信APP支付支持；
* 新增文章/页面编辑付费内容相关操作“尚未配置支付”提示；
* 优化支付配置界面交互体验；
* 优化付费内容加密界面UI；
* 优化订单列表，增加邮箱和识别码筛选支持。

= 1.2.6 =
* 修复部分主题支付链接为空问题。

= 1.2.5 =
* 新增免登录购买支持；
* 优化插件外观定制功能，支持自定义CSS及配色选择；
* 优化订单管理，增加订单类型及订单类型筛选支持；
* 优化前端付费内容购买流程；
* 修复Payjs和官方微信支付API配置共用参数bug。

= 1.2.4 =
* 新增插件版本升级提示功能；
* 兼容WP资源下载管理插件；
* 优化付费内容区域样式。

= 1.2.3 =
* 新增"如已付费购买，请[登录]"提示语；
* 新增WordPress订阅用户后台订单列表；
* 解决微信支付官方API接口time_expire报错；
* 优化支付成功返回页面刷新机制，保证加密内容刷新可见；
* 修复插件设置页面保存无提示问题；
* 修复付费文字说明样式设置无效bug。

= 1.2.2 =
* 新增PAYJS支付接口开通及配置教程；
* 移除虎皮椒及官方支付方式设置可传Logo功能；
* 优化详情页支付按钮样式，增加支付方式icon；
* 优化PAYJS接口配置参数字段名称。

= 1.2.1 =
* 新增PAYJS二维码扫码支付页面；
* 优化PAYJS接口，兼容PC端实现扫描支付；
* 优化APYJS支付设置，增加站点Logo上传选项；
* 修复PAYJS接口返回信息数据写入失败bug。

= 1.2.0 =
* 新增PAYJS支付接口；
* 新增短代码支持；
* 新增短代码使用说明；
* 优化付费价格设置，支持精确到小数点后两位。

= 1.1.0 =
* 新增注册用户付费订单页面；
* 新增付费内容小工具入口；
* 新增付费内容支付宝&微信支付可选；
* 新增插件设置界面，支持付费内容前端展示样式配置；
* 优化付费内容编辑插入按钮样式，强化功能入口。

= 1.0.0 =
* 新增付费内容内容加密功能
* 新增付费内容插件支付配置管理功能
* 新增付费内容订单管理功能
* 付费内容插件前端下单交互设计