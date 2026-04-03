<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // 父分类定义
        $parents = [
            ['name' => '常用推荐', 'slug' => 'recommend', 'icon' => 'io-fuwutuijian', 'sort_order' => 1],
            ['name' => 'AI工具', 'slug' => 'ai-tools', 'icon' => 'io-lijitougao-xiayibu', 'sort_order' => 2],
            ['name' => '办公工具', 'slug' => 'office', 'icon' => 'io-shenghuofuwu11', 'sort_order' => 3],
            ['name' => '设计创意', 'slug' => 'design', 'icon' => 'io-copyright-line', 'sort_order' => 4],
            ['name' => '开发编程', 'slug' => 'dev', 'icon' => 'io-fuwuyoujianshenghuofuwu', 'sort_order' => 5],
            ['name' => '影音娱乐', 'slug' => 'media', 'icon' => 'io-fuwutuijian', 'sort_order' => 6],
        ];

        foreach ($parents as $p) {
            Category::firstOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, ['is_active' => true, 'parent_id' => null])
            );
        }

        // 子分类 + 站点数据
        $groups = [
            'recommend' => [
                '搜索引擎' => [
                    ['title' => 'Google', 'url' => 'https://www.google.com', 'description' => '全球最大搜索引擎'],
                    ['title' => '百度', 'url' => 'https://www.baidu.com', 'description' => '中文搜索引擎'],
                    ['title' => 'Bing', 'url' => 'https://www.bing.com', 'description' => '微软搜索引擎'],
                    ['title' => '搜狗', 'url' => 'https://www.sogou.com', 'description' => '搜狗搜索'],
                    ['title' => '360搜索', 'url' => 'https://www.so.com', 'description' => '360好搜'],
                    ['title' => '头条搜索', 'url' => 'https://so.toutiao.com', 'description' => '字节跳动搜索'],
                    ['title' => 'DuckDuckGo', 'url' => 'https://duckduckgo.com', 'description' => '隐私搜索引擎'],
                    ['title' => 'Yandex', 'url' => 'https://yandex.com', 'description' => '俄罗斯搜索引擎'],
                    ['title' => 'Magi', 'url' => 'https://magi.com', 'description' => 'AI知识搜索引擎'],
                    ['title' => '夸克搜索', 'url' => 'https://quark.sm.cn', 'description' => '夸克智能搜索'],
                ],
                '社交媒体' => [
                    ['title' => '微博', 'url' => 'https://weibo.com', 'description' => '中文社交媒体'],
                    ['title' => '知乎', 'url' => 'https://www.zhihu.com', 'description' => '中文问答社区'],
                    ['title' => '豆瓣', 'url' => 'https://www.douban.com', 'description' => '书影音社区'],
                    ['title' => '小红书', 'url' => 'https://www.xiaohongshu.com', 'description' => '生活分享平台'],
                    ['title' => '即刻', 'url' => 'https://www.okjike.com', 'description' => '兴趣社交社区'],
                    ['title' => 'Twitter/X', 'url' => 'https://x.com', 'description' => '全球社交媒体'],
                    ['title' => 'Reddit', 'url' => 'https://www.reddit.com', 'description' => '全球社区论坛'],
                    ['title' => 'V2EX', 'url' => 'https://www.v2ex.com', 'description' => '创意工作者社区'],
                    ['title' => '掘金', 'url' => 'https://juejin.cn', 'description' => '开发者社区'],
                    ['title' => '贴吧', 'url' => 'https://tieba.baidu.com', 'description' => '百度贴吧'],
                ],
                '新闻资讯' => [
                    ['title' => '今日头条', 'url' => 'https://www.toutiao.com', 'description' => '个性化新闻推荐'],
                    ['title' => '澎湃新闻', 'url' => 'https://www.thepaper.cn', 'description' => '时政与思想新闻'],
                    ['title' => '新浪新闻', 'url' => 'https://news.sina.com.cn', 'description' => '新浪新闻门户'],
                    ['title' => '网易新闻', 'url' => 'https://news.163.com', 'description' => '网易新闻门户'],
                    ['title' => '腾讯新闻', 'url' => 'https://news.qq.com', 'description' => '腾讯新闻门户'],
                    ['title' => '36氪', 'url' => 'https://36kr.com', 'description' => '创业投资资讯'],
                    ['title' => '虎嗅', 'url' => 'https://www.huxiu.com', 'description' => '科技商业资讯'],
                    ['title' => '钛媒体', 'url' => 'https://www.tmtpost.com', 'description' => '科技财经资讯'],
                    ['title' => '界面新闻', 'url' => 'https://www.jiemian.com', 'description' => '商业新闻平台'],
                    ['title' => '搜狐新闻', 'url' => 'https://news.sohu.com', 'description' => '搜狐新闻门户'],
                ],
            ],
            'ai-tools' => [
                'AI对话' => [
                    ['title' => 'ChatGPT', 'url' => 'https://chat.openai.com', 'description' => 'OpenAI对话AI'],
                    ['title' => 'Claude', 'url' => 'https://claude.ai', 'description' => 'Anthropic对话AI'],
                    ['title' => '文心一言', 'url' => 'https://yiyan.baidu.com', 'description' => '百度AI助手'],
                    ['title' => '通义千问', 'url' => 'https://tongyi.aliyun.com', 'description' => '阿里AI助手'],
                    ['title' => 'Kimi', 'url' => 'https://kimi.moonshot.cn', 'description' => '月之暗面AI助手'],
                    ['title' => '豆包', 'url' => 'https://www.doubao.com', 'description' => '字节跳动AI助手'],
                    ['title' => 'DeepSeek', 'url' => 'https://chat.deepseek.com', 'description' => '深度求索AI'],
                    ['title' => 'Gemini', 'url' => 'https://gemini.google.com', 'description' => 'Google AI助手'],
                    ['title' => 'Perplexity', 'url' => 'https://www.perplexity.ai', 'description' => 'AI搜索引擎'],
                    ['title' => 'Coze', 'url' => 'https://www.coze.com', 'description' => '字节AI开发平台'],
                ],
                'AI写作' => [
                    ['title' => 'Notion AI', 'url' => 'https://www.notion.so', 'description' => 'AI笔记与写作'],
                    ['title' => 'Jasper', 'url' => 'https://www.jasper.ai', 'description' => 'AI营销文案'],
                    ['title' => 'Copy.ai', 'url' => 'https://www.copy.ai', 'description' => 'AI文案生成'],
                    ['title' => 'Writesonic', 'url' => 'https://writesonic.com', 'description' => 'AI内容创作'],
                    ['title' => '秘塔写作猫', 'url' => 'https://xiezuocat.com', 'description' => '中文AI写作'],
                    ['title' => '讯飞写作', 'url' => 'https://huatong.xfyun.cn', 'description' => '科大讯飞AI写作'],
                    ['title' => '火龙果写作', 'url' => 'https://www.mypitaya.com', 'description' => 'AI智能写作'],
                    ['title' => '深言达意', 'url' => 'https://www.shenyandayi.com', 'description' => 'AI遣词造句'],
                    ['title' => '沉浸式翻译', 'url' => 'https://immersivetranslate.com', 'description' => 'AI双语翻译'],
                    ['title' => 'QuillBot', 'url' => 'https://quillbot.com', 'description' => 'AI改写润色'],
                ],
                'AI绘画' => [
                    ['title' => 'Midjourney', 'url' => 'https://www.midjourney.com', 'description' => 'AI图像生成'],
                    ['title' => 'DALL-E', 'url' => 'https://openai.com/dall-e', 'description' => 'OpenAI绘画'],
                    ['title' => 'Stable Diffusion', 'url' => 'https://stability.ai', 'description' => '开源AI绘画'],
                    ['title' => '文心一格', 'url' => 'https://yige.baidu.com', 'description' => '百度AI绘画'],
                    ['title' => '即梦AI', 'url' => 'https://jimeng.jianying.com', 'description' => '字节AI绘画'],
                    ['title' => '通义万相', 'url' => 'https://tongyi.aliyun.com/wanxiang', 'description' => '阿里AI绘画'],
                    ['title' => 'Leonardo AI', 'url' => 'https://leonardo.ai', 'description' => 'AI创意设计'],
                    ['title' => 'Playground', 'url' => 'https://playground.com', 'description' => 'AI图像创作'],
                    ['title' => 'Ideogram', 'url' => 'https://ideogram.ai', 'description' => 'AI文字图像'],
                    ['title' => 'Flux', 'url' => 'https://blackforestlabs.ai', 'description' => '新一代AI绘画'],
                ],
            ],
            'office' => [
                '云存储' => [
                    ['title' => '百度网盘', 'url' => 'https://pan.baidu.com', 'description' => '百度云存储'],
                    ['title' => '阿里云盘', 'url' => 'https://www.aliyundrive.com', 'description' => '阿里云存储'],
                    ['title' => '腾讯微云', 'url' => 'https://www.weiyun.com', 'description' => '腾讯云存储'],
                    ['title' => '夸克网盘', 'url' => 'https://pan.quark.cn', 'description' => '夸克云存储'],
                    ['title' => '蓝奏云', 'url' => 'https://www.lanzou.com', 'description' => '免费网盘'],
                    ['title' => 'OneDrive', 'url' => 'https://onedrive.live.com', 'description' => '微软云存储'],
                    ['title' => 'Google Drive', 'url' => 'https://drive.google.com', 'description' => '谷歌云存储'],
                    ['title' => '坚果云', 'url' => 'https://www.jianguoyun.com', 'description' => '同步网盘'],
                ],
                '在线文档' => [
                    ['title' => '腾讯文档', 'url' => 'https://docs.qq.com', 'description' => '腾讯在线文档'],
                    ['title' => '飞书文档', 'url' => 'https://www.feishu.cn', 'description' => '字节协同办公'],
                    ['title' => '石墨文档', 'url' => 'https://shimo.im', 'description' => '在线协作文档'],
                    ['title' => '语雀', 'url' => 'https://www.yuque.com', 'description' => '知识管理平台'],
                    ['title' => 'Notion', 'url' => 'https://www.notion.so', 'description' => '全能笔记平台'],
                    ['title' => '金山文档', 'url' => 'https://www.kdocs.cn', 'description' => 'WPS在线文档'],
                    ['title' => '幕布', 'url' => 'https://mubu.com', 'description' => '大纲笔记工具'],
                    ['title' => '印象笔记', 'url' => 'https://www.yinxiang.com', 'description' => '知识管理工具'],
                ],
                '邮箱通讯' => [
                    ['title' => 'QQ邮箱', 'url' => 'https://mail.qq.com', 'description' => 'QQ邮箱'],
                    ['title' => '163邮箱', 'url' => 'https://mail.163.com', 'description' => '网易邮箱'],
                    ['title' => 'Gmail', 'url' => 'https://mail.google.com', 'description' => '谷歌邮箱'],
                    ['title' => 'Outlook', 'url' => 'https://outlook.live.com', 'description' => '微软邮箱'],
                    ['title' => '新浪邮箱', 'url' => 'https://mail.sina.com.cn', 'description' => '新浪邮箱'],
                    ['title' => '钉钉', 'url' => 'https://www.dingtalk.com', 'description' => '企业协同办公'],
                    ['title' => '企业微信', 'url' => 'https://work.weixin.qq.com', 'description' => '企业沟通工具'],
                    ['title' => '飞书', 'url' => 'https://www.feishu.cn', 'description' => '企业协作平台'],
                ],
            ],
            'design' => [
                '设计工具' => [
                    ['title' => 'Figma', 'url' => 'https://www.figma.com', 'description' => '在线设计工具'],
                    ['title' => 'Canva', 'url' => 'https://www.canva.com', 'description' => '在线平面设计'],
                    ['title' => '即时设计', 'url' => 'https://js.design', 'description' => '国产在线设计'],
                    ['title' => 'MasterGo', 'url' => 'https://mastergo.com', 'description' => '产品设计平台'],
                    ['title' => 'Pixso', 'url' => 'https://pixso.cn', 'description' => '一站式设计工具'],
                    ['title' => 'Sketch', 'url' => 'https://www.sketch.com', 'description' => 'Mac设计工具'],
                    ['title' => 'Adobe XD', 'url' => 'https://www.adobe.com/products/xd', 'description' => 'Adobe体验设计'],
                    ['title' => 'Framer', 'url' => 'https://www.framer.com', 'description' => '网页设计发布'],
                ],
                '素材资源' => [
                    ['title' => 'Dribbble', 'url' => 'https://dribbble.com', 'description' => '设计师作品展示'],
                    ['title' => 'Behance', 'url' => 'https://www.behance.net', 'description' => 'Adobe创意社区'],
                    ['title' => 'Pinterest', 'url' => 'https://www.pinterest.com', 'description' => '创意灵感收集'],
                    ['title' => '花瓣网', 'url' => 'https://huaban.com', 'description' => '设计灵感采集'],
                    ['title' => '站酷', 'url' => 'https://www.zcool.com.cn', 'description' => '设计师社区'],
                    ['title' => 'Unsplash', 'url' => 'https://unsplash.com', 'description' => '免费高清图片'],
                    ['title' => 'Pexels', 'url' => 'https://www.pexels.com', 'description' => '免费图片素材'],
                    ['title' => 'Iconfont', 'url' => 'https://www.iconfont.cn', 'description' => '阿里巴巴图标库'],
                    ['title' => 'Font Awesome', 'url' => 'https://fontawesome.com', 'description' => '图标字体库'],
                    ['title' => 'UI中国', 'url' => 'https://www.ui.cn', 'description' => 'UI设计师社区'],
                ],
                '配色字体' => [
                    ['title' => 'Coolors', 'url' => 'https://coolors.co', 'description' => '配色方案生成'],
                    ['title' => 'Color Hunt', 'url' => 'https://colorhunt.co', 'description' => '配色灵感'],
                    ['title' => '中国色', 'url' => 'http://zhongguose.com', 'description' => '中国传统色彩'],
                    ['title' => 'Google Fonts', 'url' => 'https://fonts.google.com', 'description' => '谷歌免费字体'],
                    ['title' => '字由', 'url' => 'https://www.hellofont.cn', 'description' => '中文字体管理'],
                    ['title' => '求字体', 'url' => 'https://www.qiuziti.com', 'description' => '字体识别下载'],
                    ['title' => 'Adobe Color', 'url' => 'https://color.adobe.com', 'description' => 'Adobe配色工具'],
                    ['title' => 'WebGradients', 'url' => 'https://webgradients.com', 'description' => '渐变色合集'],
                ],
            ],
            'dev' => [
                '代码托管' => [
                    ['title' => 'GitHub', 'url' => 'https://github.com', 'description' => '全球代码托管平台'],
                    ['title' => 'GitLab', 'url' => 'https://gitlab.com', 'description' => 'DevOps平台'],
                    ['title' => 'Gitee', 'url' => 'https://gitee.com', 'description' => '国产代码托管'],
                    ['title' => 'Bitbucket', 'url' => 'https://bitbucket.org', 'description' => 'Atlassian代码托管'],
                    ['title' => 'Coding', 'url' => 'https://coding.net', 'description' => '一站式开发平台'],
                    ['title' => 'SourceForge', 'url' => 'https://sourceforge.net', 'description' => '开源软件托管'],
                    ['title' => 'npm', 'url' => 'https://www.npmjs.com', 'description' => 'Node包管理'],
                    ['title' => 'Packagist', 'url' => 'https://packagist.org', 'description' => 'PHP包管理'],
                ],
                '开发文档' => [
                    ['title' => 'MDN Web Docs', 'url' => 'https://developer.mozilla.org', 'description' => 'Web开发文档'],
                    ['title' => 'Stack Overflow', 'url' => 'https://stackoverflow.com', 'description' => '开发者问答'],
                    ['title' => 'Laravel', 'url' => 'https://laravel.com', 'description' => 'PHP Web框架'],
                    ['title' => 'Vue.js', 'url' => 'https://vuejs.org', 'description' => '前端框架'],
                    ['title' => 'React', 'url' => 'https://react.dev', 'description' => 'UI组件库'],
                    ['title' => 'Tailwind CSS', 'url' => 'https://tailwindcss.com', 'description' => 'CSS工具框架'],
                    ['title' => 'TypeScript', 'url' => 'https://www.typescriptlang.org', 'description' => '类型化JS'],
                    ['title' => 'Next.js', 'url' => 'https://nextjs.org', 'description' => 'React全栈框架'],
                    ['title' => 'Vite', 'url' => 'https://vitejs.dev', 'description' => '前端构建工具'],
                    ['title' => 'Node.js', 'url' => 'https://nodejs.org', 'description' => 'JS运行时'],
                ],
                '开发工具' => [
                    ['title' => 'VS Code', 'url' => 'https://code.visualstudio.com', 'description' => '微软代码编辑器'],
                    ['title' => 'CodePen', 'url' => 'https://codepen.io', 'description' => '前端代码演示'],
                    ['title' => 'JSFiddle', 'url' => 'https://jsfiddle.net', 'description' => '在线代码编辑'],
                    ['title' => 'CodeSandbox', 'url' => 'https://codesandbox.io', 'description' => '在线开发环境'],
                    ['title' => 'StackBlitz', 'url' => 'https://stackblitz.com', 'description' => '浏览器IDE'],
                    ['title' => 'Regex101', 'url' => 'https://regex101.com', 'description' => '正则表达式测试'],
                    ['title' => 'Postman', 'url' => 'https://www.postman.com', 'description' => 'API调试工具'],
                    ['title' => 'Can I Use', 'url' => 'https://caniuse.com', 'description' => '浏览器兼容性'],
                ],
            ],
            'media' => [
                '视频平台' => [
                    ['title' => '哔哩哔哩', 'url' => 'https://www.bilibili.com', 'description' => '弹幕视频网站'],
                    ['title' => 'YouTube', 'url' => 'https://www.youtube.com', 'description' => '全球视频平台'],
                    ['title' => '抖音', 'url' => 'https://www.douyin.com', 'description' => '短视频平台'],
                    ['title' => '腾讯视频', 'url' => 'https://v.qq.com', 'description' => '腾讯视频平台'],
                    ['title' => '爱奇艺', 'url' => 'https://www.iqiyi.com', 'description' => '在线视频平台'],
                    ['title' => '优酷', 'url' => 'https://www.youku.com', 'description' => '在线视频平台'],
                    ['title' => '快手', 'url' => 'https://www.kuaishou.com', 'description' => '短视频平台'],
                    ['title' => '芒果TV', 'url' => 'https://www.mgtv.com', 'description' => '湖南卫视平台'],
                    ['title' => 'Netflix', 'url' => 'https://www.netflix.com', 'description' => '流媒体平台'],
                    ['title' => 'Disney+', 'url' => 'https://www.disneyplus.com', 'description' => '迪士尼流媒体'],
                ],
                '音乐平台' => [
                    ['title' => '网易云音乐', 'url' => 'https://music.163.com', 'description' => '音乐社区平台'],
                    ['title' => 'QQ音乐', 'url' => 'https://y.qq.com', 'description' => '腾讯音乐平台'],
                    ['title' => 'Spotify', 'url' => 'https://www.spotify.com', 'description' => '全球音乐平台'],
                    ['title' => 'Apple Music', 'url' => 'https://music.apple.com', 'description' => '苹果音乐'],
                    ['title' => '酷狗音乐', 'url' => 'https://www.kugou.com', 'description' => '数字音乐平台'],
                    ['title' => '酷我音乐', 'url' => 'https://www.kuwo.cn', 'description' => '在线音乐平台'],
                    ['title' => '汽水音乐', 'url' => 'https://qishui.douyin.com', 'description' => '字节音乐平台'],
                    ['title' => '咪咕音乐', 'url' => 'https://music.migu.cn', 'description' => '移动音乐平台'],
                ],
            ],
        ];

        foreach ($groups as $parentSlug => $children) {
            $parent = Category::where('slug', $parentSlug)->first();
            if (!$parent) continue;

            $childSort = 1;
            foreach ($children as $childName => $sites) {
                $childSlug = Str::slug($childName) ?: 'cat-' . substr(md5($childName), 0, 8);
                $child = Category::firstOrCreate(
                    ['slug' => $childSlug],
                    [
                        'name' => $childName,
                        'is_active' => true,
                        'parent_id' => $parent->id,
                        'sort_order' => $childSort++,
                    ]
                );

                $siteSort = 1;
                foreach ($sites as $s) {
                    Site::firstOrCreate(
                        ['url' => $s['url']],
                        [
                            'category_id' => $child->id,
                            'title' => $s['title'],
                            'description' => $s['description'] ?? null,
                            'favicon_url' => 'https://t2.gstatic.cn/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&size=64&url=' . urlencode($s['url']),
                            'is_public' => true,
                            'is_active' => true,
                            'sort_order' => $siteSort++,
                            'clicks' => rand(10, 500),
                        ]
                    );
                }
            }
        }
    }
}
