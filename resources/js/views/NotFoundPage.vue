<template>
  <div class="not-found-page">
    <div class="not-found-content">
      <div class="not-found-code">404</div>
      <h1 class="not-found-title">页面不存在</h1>
      <p class="not-found-desc">抱歉，您访问的页面可能已被删除或暂时不可用</p>
      <router-link to="/" class="not-found-btn">返回首页</router-link>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.not-found-page {
  min-height: calc(100vh - var(--main-nav-height, 56px) - 200px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.not-found-content {
  text-align: center;
}

.not-found-code {
  font-size: 120px;
  font-weight: 800;
  line-height: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 8px;
  user-select: none;
}

.not-found-title {
  font-size: 24px;
  font-weight: 600;
  color: var(--main-color);
  margin: 0 0 8px;
}

.not-found-desc {
  font-size: 14px;
  color: var(--muted-color);
  margin: 0 0 32px;
}

.not-found-btn {
  display: inline-block;
  padding: 10px 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: opacity 0.2s, transform 0.2s;
}

.not-found-btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}
</style>
