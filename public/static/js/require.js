/*
 * @Author: iowen
 * @Author URI: https://www.iowen.cn/
 * @Date: 2023-02-01 00:01:43
 * @LastEditors: iowen
 * @LastEditTime: 2025-05-10 17:15:48
 * @FilePath: /onenav/assets/js/require.js
 * @Description: 模块加载器
 */

(function (global) {
  var loadedScripts = {}; 
  var config = {   
    baseUrl: '',
    urlArgs: '',
    paths: {}
  };

  // 初始化 require 模块
  var require = {
    config: function (options) {
      config = Object.assign(config, options);
    },

    // 检查是否是绝对 URL
    isAbsoluteUrl: function (url) {
      return /^(https?:)?\/\//.test(url);
    },

    loadScript: function (url, callback) {
      if (loadedScripts[url]) {
        callback(true);
        return;
      }

      var script = document.createElement('script');
      script.src = url;
      script.onload = function () {
        loadedScripts[url] = true;
        callback(true);
      };
      script.onerror = function () {
        callback(false);
        console.error('Error loading script:', url);
      };
      document.head.appendChild(script);
    },

    // 加载单个或多个 URL，数组情况
    loadScriptsArray: function (urls, callback, errorCallback) {
      var index = 0;

      function tryLoadNext() {
        if (index >= urls.length) {
          callback();
          return;
        }

        require.loadScript(urls[index], function (success) {
          if (!success) {
            errorCallback && errorCallback(urls[index]);
          } else {
            index++;
            tryLoadNext();
          }
        });
      }

      tryLoadNext();
    },

    load: function (deps, callback) {
      if (typeof deps === 'string') {
        deps = [deps];
      }

      var loadedCount = 0;
      var totalDeps = deps.length;

      function onLoad() {
        loadedCount++;
        if (loadedCount === totalDeps && typeof callback === 'function') {
          callback();
        }
      }

      deps.forEach(function (dep) {
        var path = config.paths[dep] ? config.paths[dep] : dep;
        var urls;

        if (Array.isArray(path)) {
          urls = path;
        } else {
          urls = [path];
        }

        urls = urls.map(function (url) {
          if (!require.isAbsoluteUrl(url)) {
            url = [config.baseUrl + '/' + url + '.js'];  // 拼接 baseUrl
          }
          if (config.urlArgs) {
            return url + (url.indexOf('?') === -1 ? '?' : '&') + config.urlArgs;
          }
          return url;
        });

        require.loadScriptsArray(urls, onLoad, function (failedUrl) {
          console.error('Failed to load dependency:', dep, 'URL:', failedUrl); // 输出详细错误信息
        });
      });
    }
  };

  global.ioRequire = function (deps, callback) {
    require.load(deps, callback);
  };

  global.ioRequire.config = require.config;

})(window);

// 示例配置
ioRequire.config({
  baseUrl: IO.uri + '/assets/js',
  urlArgs: 'ver=' + IO.version,
  paths: {
    'main': 'main' + IO.minAssets,
    'lazyload': 'lazyload.min',
    'captcha': 'captcha',
    'comments': [IO.homeUrl + '/wp-includes/js/comment-reply.min.js', 'comments-ajax'],
    'sticky-sidebar': 'theia-sticky-sidebar',
    'slidercaptcha': 'longbow.slidercaptcha' + IO.minAssets,
    'color-thief': 'color-thief.umd',
    'fancybox': 'jquery.fancybox.min',
    'echarts': ['echarts.min', 'sites-chart'],
    'pay': IO.uri + '/iopay/assets/js/pay.js',
    'new-post': ['new-post'],
    'weather': '//widget.seniverse.com/widget/chameleon.js',
    'weather-v2': '//cdn.sencdn.com/widget2/static/js/bundle.js?t=' + parseInt((new Date().getTime() / 100000000).toString(), 10),
    'swiper': 'swiper-bundle.min',
  }
});

document.querySelectorAll(".panel-body.single img").forEach(function(img) {
  // 如果图片不是 wp-smiley 并且没有被包裹在 <a> 中
  if (!img.classList.contains('unfancybox') && 
      !img.classList.contains('wp-smiley') && 
      !img.parentElement.matches('a')) {
    
    var imgSrc = IO.lazyload ? img.dataset.src : img.src;
    var caption = img.alt || '';
    imgSrc = imgSrc || img.src;

    var link = document.createElement('a');
    link.className = 'js';
    link.href = imgSrc;
    link.setAttribute('data-fancybox', 'images');
    link.setAttribute('data-caption', caption);
    
    img.parentNode.insertBefore(link, img);
    link.appendChild(img);
  }
  
  if (IO.lazyload && img.dataset.src && !img.classList.contains('lazy')) {
    img.classList.add('lazy');
  }
});


//ioRequire(['lazyload','main']);
//ioRequire('main');
// lazyload.min.js and main.min.js are WordPress theme files not present in Laravel
// ioRequire('lazyload', function () {
//   ioRequire('main');
// });
