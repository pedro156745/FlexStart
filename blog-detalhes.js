// blog-detalhes.js - Carregamento de posts individuais

document.addEventListener('DOMContentLoaded', function() {
  loadPost();
  loadSidebar();
  
  // Configurar compartilhamento
  setupSharing();
  
  // Configurar formulário de comentários
  document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Comentário enviado com sucesso! Em uma implementação real, isso seria salvo em um banco de dados.');
    this.reset();
  });
});

function loadPost() {
  // Obter ID do post da URL
  const urlParams = new URLSearchParams(window.location.search);
  const postId = urlParams.get('id');
  
  if (!postId) {
    document.getElementById('post-title').textContent = 'Post não encontrado';
    document.getElementById('post-content').innerHTML = '<p>O post solicitado não foi encontrado.</p>';
    return;
  }
  
  // Carregar post do localStorage
  const posts = JSON.parse(localStorage.getItem('blogPosts') || '[]');
  const post = posts.find(p => p.id === postId);
  
  if (!post) {
    document.getElementById('post-title').textContent = 'Post não encontrado';
    document.getElementById('post-content').innerHTML = '<p>O post solicitado não foi encontrado ou foi removido.</p>';
    return;
  }
  
  // Preencher dados do post
  document.getElementById('post-title').textContent = post.title;
  document.getElementById('breadcrumb-category').textContent = post.category;
  document.getElementById('post-date').textContent = formatDate(post.date);
  document.getElementById('post-image').src = post.image;
  document.getElementById('post-image').alt = post.title;
  document.getElementById('post-content').innerHTML = post.content;
  
  // Calcular tempo de leitura (aproximado)
  const wordCount = post.content.split(/\s+/).length;
  const readTime = Math.ceil(wordCount / 200); // 200 palavras por minuto
  document.getElementById('post-read-time').textContent = `${readTime} min de leitura`;
  
  // Carregar tags
  const tagsContainer = document.getElementById('post-tags');
  tagsContainer.innerHTML = '';
  
  if (post.tags && post.tags.length > 0) {
    post.tags.forEach(tag => {
      if (tag.trim()) {
        const tagElement = document.createElement('a');
        tagElement.href = `blog.html?tag=${encodeURIComponent(tag.trim())}`;
        tagElement.textContent = tag.trim();
        tagsContainer.appendChild(tagElement);
      }
    });
  } else {
    tagsContainer.innerHTML = '<span class="text-muted">Nenhuma tag</span>';
  }
  
  // Atualizar título da página
  document.title = `${post.title} - Blog Anpha Web`;
}

function loadSidebar() {
  loadCategories();
  loadRecentPosts();
  loadPopularTags();
}

function loadCategories() {
  const categories = JSON.parse(localStorage.getItem('blogCategories') || '[]');
  const posts = JSON.parse(localStorage.getItem('blogPosts') || '[]');
  
  const categoriesContainer = document.getElementById('sidebar-categories');
  categoriesContainer.innerHTML = '';
  
  categories.forEach(category => {
    const postCount = posts.filter(post => post.category === category.name && post.status === 'published').length;
    
    const li = document.createElement('li');
    li.innerHTML = `
      <a href="blog.html?category=${encodeURIComponent(category.name)}">
        ${category.name} <span class="text-muted">(${postCount})</span>
      </a>
    `;
    categoriesContainer.appendChild(li);
  });
}

function loadRecentPosts() {
  const posts = JSON.parse(localStorage.getItem('blogPosts') || '[]')
    .filter(post => post.status === 'published')
    .sort((a, b) => new Date(b.date) - new Date(a.date))
    .slice(0, 5);
  
  const container = document.getElementById('sidebar-recent-posts');
  container.innerHTML = '';
  
  if (posts.length === 0) {
    container.innerHTML = '<p class="text-muted">Nenhum post recente</p>';
    return;
  }
  
  posts.forEach(post => {
    const postElement = document.createElement('div');
    postElement.className = 'recent-post';
    postElement.innerHTML = `
      <img src="${post.image}" alt="${post.title}">
      <div>
        <h5><a href="blog-detalhes.html?id=${post.id}">${post.title}</a></h5>
        <div class="date">${formatDate(post.date)}</div>
      </div>
    `;
    container.appendChild(postElement);
  });
}

function loadPopularTags() {
  const posts = JSON.parse(localStorage.getItem('blogPosts') || '[]');
  const allTags = [];
  
  // Coletar todas as tags
  posts.forEach(post => {
    if (post.tags && Array.isArray(post.tags)) {
      post.tags.forEach(tag => {
        if (tag.trim()) {
          allTags.push(tag.trim());
        }
      });
    }
  });
  
  // Contar ocorrências
  const tagCounts = {};
  allTags.forEach(tag => {
    tagCounts[tag] = (tagCounts[tag] || 0) + 1;
  });
  
  // Ordenar por popularidade e pegar as top 10
  const popularTags = Object.entries(tagCounts)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
    .map(entry => entry[0]);
  
  const container = document.getElementById('sidebar-tags');
  container.innerHTML = '';
  
  popularTags.forEach(tag => {
    const tagElement = document.createElement('a');
    tagElement.href = `blog.html?tag=${encodeURIComponent(tag)}`;
    tagElement.textContent = tag;
    container.appendChild(tagElement);
  });
}

function setupSharing() {
  const currentUrl = encodeURIComponent(window.location.href);
  const postTitle = encodeURIComponent(document.getElementById('post-title').textContent);
  
  const shareButtons = document.querySelectorAll('.share-buttons a');
  
  shareButtons[0].href = `https://www.facebook.com/sharer/sharer.php?u=${currentUrl}`;
  shareButtons[1].href = `https://twitter.com/intent/tweet?text=${postTitle}&url=${currentUrl}`;
  shareButtons[2].href = `https://www.linkedin.com/sharing/share-offsite/?url=${currentUrl}`;
  shareButtons[3].href = `https://api.whatsapp.com/send?text=${postTitle} ${currentUrl}`;
  
  // Abrir em nova janela
  shareButtons.forEach(button => {
    button.target = '_blank';
  });
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}