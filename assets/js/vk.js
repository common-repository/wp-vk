tinymce.PluginManager.add("vk_mark",(function(t){return t.on("BeforeSetContent",(function(t){-1!==t.content.indexOf("\x3c!--以下为付费内容--\x3e")&&(t.content=t.content.replace(/<!--以下为付费内容-->/g,'<img src="'+tinymce.Env.transparentSrc+'" data-vk-mark="vk-mark" class="wp-more-tag mce-vk-mark" alt="" title="以下为付费内容" data-mce-resize="false" data-mce-placeholder="1" />'))})),t.on("PostProcess",(function(t){t.get&&(t.content=t.content.replace(/<img[^>]+>/g,(function(t){var a;return-1!==t.indexOf('data-vk-mark="vk-mark"')&&(a="\x3c!--以下为付费内容--\x3e"),a||t})))})),{init:function(t,a){t.addButton("vk_mark",{title:"付费内容分隔",image:"data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMiIgaGVpZ2h0PSIyMiIgZmlsbD0ibm9uZSI+PHBhdGggc3Ryb2tlPSIjMDZDIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIHN0cm9rZS13aWR0aD0iMiIgZD0iTTExIDJ2MGE0LjMgNC4zIDAgMDAtNC40IDQuM1Y5aDguNlY2LjNDMTUuMiA0IDEzLjMgMiAxMSAydjB6TTQgOWgxMy44djEySDRWOXoiIGNsaXAtcnVsZT0iZXZlbm9kZCIvPjxwYXRoIHN0cm9rZT0iIzA2QyIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBzdHJva2Utd2lkdGg9IjIiIGQ9Ik0xMSAxMi40YTEuNyAxLjcgMCAxMTAgMy40IDEuNyAxLjcgMCAwMTAtMy40djB6IiBjbGlwLXJ1bGU9ImV2ZW5vZGQiLz48cGF0aCBzdHJva2U9IiMwNkMiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgc3Ryb2tlLXdpZHRoPSIyIiBkPSJNMTEgMTUuOHYxLjciLz48L3N2Zz4=",onclick:function(){t.execCommand("mceInsertContent",!1,"\x3c!--以下为付费内容--\x3e"),vk_set_pay||wbui.alert('您尚未配置支付，<a href="'+vk_set_pay_url+'" target="_blank">去设置</a>')}})},createControl:function(t,a){return null},getInfo:function(){return null}}}));