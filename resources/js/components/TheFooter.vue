<template>
  <footer class="main-footer">
    <div class="footer-inner">
      <div class="footer-grid">
        <div class="footer-brand">
          <img :src="'/static/image/logo.png'" alt="导航" class="footer-logo" />
          <p class="footer-desc">{{ siteName }} 上班人必备的职场办公导航网站</p>
        </div>
        <div class="footer-links-col">
          <div class="footer-links">
            <a href="#">用户协议</a>
            <a href="#">免责声明</a>
            <a href="#">关于本站</a>
            <a href="#">广告合作</a>
            <a href="#">收录投稿</a>
            <a href="#">友链申请</a>
          </div>
        </div>
        <div class="footer-qrcode">
          <div class="qrcode-box">
            <img :src="'/static/image/1738680205-qrcode_for_gh_27bdb6e28932_258.jpg'" alt="关注公众号" />
          </div>
          <span class="qrcode-label">关注公众号</span>
        </div>
      </div>
      <div class="footer-copyright">
        Copyright &copy; 2026 {{ siteName }}
      </div>
    </div>
  </footer>
</template>

<script setup>
const siteName = document.querySelector('meta[name="description"]')?.content || '导航';
</script>
