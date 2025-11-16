// blog-admin.js - Sistema de Gerenciamento do Blog

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
  loadDashboard();
  loadCategoriesForSelect();
  loadRecentPosts();
  loadAllPosts();
  loadCategoriesTable();
  
  // Event listeners
  document.getElementById('postForm').addEventListener('submit', savePost);
  document.getElementById('postImage').addEventListener('change', previewImage);
  document.getElementById('categoryName').addEventListener('input', generateSlug);
});

// Funções de navegação
function showSection(sectionId) {
  // Esconder todas as seções
  document.querySelectorAll('.section').forEach(section => {
    section.classList.add('d-none');
  });
  
  // Mostrar a seção selecionada
  document.getElementById(sectionId).classList.remove('d-none');
  
  // Atualizar dados se necessário
  if (sectionId === 'list-posts') {
    loadAllPosts();
  } else if (sectionId === 'categories') {
    loadCategoriesTable();
  } else if (sectionId === 'dashboard') {
    loadDashboard();
  }
}

// Gerenciamento de Posts
function savePost(e) {
  e.preventDefault();
  
  const postId = document.getElementById('postId').value;
  const postData = {
    id: postId || Date.now().toString(),
    title: document.getElementById('postTitle').value,
    content: document.getElementById('postContent').value,
    excerpt: document.getElementById('postExcerpt').value,
    status: document.getElementById('postStatus').value,
    category: document.getElementById('postCategory').value,
    tags: document.getElementById('postTags').value.split(',').map(tag => tag.trim()),
    date: document.getElementById('postDate').value || new Date().toISOString().slice(0, 16),
    image: document.getElementById('imagePreview').src || 'assets/img/blog/default.jpg'
  };
  
  // Salvar no localStorage
  const posts = getPosts();
  
  if (postId) {
    // Atualizar post existente
    const index = posts.findIndex(post => post.id === postId);
    if (index !== -1) {
      posts[index] = postData;
    }
  } else {
    // Adicionar novo post
    posts.push(postData);
  }
  
  localStorage.setItem('blogPosts', JSON.stringify(posts));
  
  // Feedback para o usuário
  alert(postId ? 'Post atualizado com sucesso!' : 'Post criado com sucesso!');
  
  // Redirecionar para a lista de posts
  showSection('list-posts');
  resetForm();
}

function loadRecentPosts() {
  const posts = getPosts().slice(0, 5); // Últimos 5 posts
  const tableBody = document.getElementById('recentPostsTable');
  
  tableBody.innerHTML = '';
  
  posts.forEach(post => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${post.title}</td>
      <td>${post.category}</td>
      <td>${formatDate(post.date)}</td>
      <td><span class="badge ${post.status === 'published' ? 'bg-success' : 'bg-warning'} badge-status">${post.status === 'published' ? 'Publicado' : 'Rascunho'}</span></td>
      <td class="action-buttons">
        <button class="btn btn-sm btn-outline-primary" onclick="editPost('${post.id}')">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="deletePost('${post.id}')">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    `;
    tableBody.appendChild(row);
  });
}

function loadAllPosts() {
  const posts = getPosts();
  const tableBody = document.getElementById('allPostsTable');
  
  tableBody.innerHTML = '';
  
  posts.forEach(post => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${post.title}</td>
      <td>${post.category}</td>
      <td>${formatDate(post.date)}</td>
      <td><span class="badge ${post.status === 'published' ? 'bg-success' : 'bg-warning'} badge-status">${post.status === 'published' ? 'Publicado' : 'Rascunho'}</span></td>
      <td class="action-buttons">
        <button class="btn btn-sm btn-outline-primary" onclick="editPost('${post.id}')">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="deletePost('${post.id}')">
          <i class="bi bi-trash"></i>
        </button>
        <button class="btn btn-sm btn-outline-info" onclick="viewPost('${post.id}')">
          <i class="bi bi-eye"></i>
        </button>
      </td>
    `;
    tableBody.appendChild(row);
  });
}

function editPost(id) {
  const posts = getPosts();
  const post = posts.find(p => p.id === id);
  
  if (post) {
    document.getElementById('postId').value = post.id;
    document.getElementById('postTitle').value = post.title;
    document.getElementById('postContent').value = post.content;
    document.getElementById('postExcerpt').value = post.excerpt;
    document.getElementById('postStatus').value = post.status;
    document.getElementById('postCategory').value = post.category;
    document.getElementById('postTags').value = post.tags.join(', ');
    document.getElementById('postDate').value = post.date.slice(0, 16);
    
    if (post.image) {
      document.getElementById('imagePreview').src = post.image;
      document.getElementById('imagePreview').style.display = 'block';
    }
    
    document.getElementById('formTitle').textContent = 'Editar Post';
    document.getElementById('submitButton').innerHTML = '<i class="bi bi-check-circle me-1"></i> Atualizar Post';
    
    showSection('add-post');
  }
}

function deletePost(id) {
  if (confirm('Tem certeza que deseja excluir este post?')) {
    const posts = getPosts();
    const filteredPosts = posts.filter(post => post.id !== id);
    localStorage.setItem('blogPosts', JSON.stringify(filteredPosts));
    
    loadRecentPosts();
    loadAllPosts();
    loadDashboard();
  }
}

function viewPost(id) {
  window.open(`blog-detalhes.html?id=${id}`, '_blank');
}

function filterPosts() {
  // Implementação básica de filtro
  const statusFilter = document.getElementById('filterStatus').value;
  const categoryFilter = document.getElementById('filterCategory').value;
  
  let posts = getPosts();
  
  if (statusFilter !== 'all') {
    posts = posts.filter(post => post.status === statusFilter);
  }
  
  if (categoryFilter !== 'all') {
    posts = posts.filter(post => post.category === categoryFilter);
  }
  
  const tableBody = document.getElementById('allPostsTable');
  tableBody.innerHTML = '';
  
  posts.forEach(post => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${post.title}</td>
      <td>${post.category}</td>
      <td>${formatDate(post.date)}</td>
      <td><span class="badge ${post.status === 'published' ? 'bg-success' : 'bg-warning'} badge-status">${post.status === 'published' ? 'Publicado' : 'Rascunho'}</span></td>
      <td class="action-buttons">
        <button class="btn btn-sm btn-outline-primary" onclick="editPost('${post.id}')">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="deletePost('${post.id}')">
          <i class="bi bi-trash"></i>
        </button>
        <button class="btn btn-sm btn-outline-info" onclick="viewPost('${post.id}')">
          <i class="bi bi-eye"></i>
        </button>
      </td>
    `;
    tableBody.appendChild(row);
  });
}

function resetForm() {
  document.getElementById('postForm').reset();
  document.getElementById('postId').value = '';
  document.getElementById('imagePreview').style.display = 'none';
  document.getElementById('formTitle').textContent = 'Adicionar Novo Post';
  document.getElementById('submitButton').innerHTML = '<i class="bi bi-check-circle me-1"></i> Publicar Post';
  document.getElementById('postDate').value = new Date().toISOString().slice(0, 16);
}

// Gerenciamento de Categorias
function saveCategory() {
  const categoryId = document.getElementById('categoryId').value;
  const categoryData = {
    id: categoryId || Date.now().toString(),
    name: document.getElementById('categoryName').value,
    slug: document.getElementById('categorySlug').value || document.getElementById('categoryName').value.toLowerCase().replace(/\s+/g, '-'),
    description: document.getElementById('categoryDescription').value
  };
  
  const categories = getCategories();
  
  if (categoryId) {
    // Atualizar categoria existente
    const index = categories.findIndex(cat => cat.id === categoryId);
    if (index !== -1) {
      categories[index] = categoryData;
    }
  } else {
    // Adicionar nova categoria
    categories.push(categoryData);
  }
  
  localStorage.setItem('blogCategories', JSON.stringify(categories));
  
  // Fechar modal e atualizar interface
  bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
  loadCategoriesForSelect();
  loadCategoriesTable();
  loadDashboard();
  
  // Limpar formulário
  document.getElementById('categoryForm').reset();
  document.getElementById('categoryId').value = '';
}

function loadCategoriesTable() {
  const categories = getCategories();
  const tableBody = document.getElementById('categoriesTable');
  
  tableBody.innerHTML = '';
  
  categories.forEach(category => {
    const posts = getPosts().filter(post => post.category === category.name);
    
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${category.name}</td>
      <td>${category.slug}</td>
      <td>${category.description || '-'}</td>
      <td>${posts.length}</td>
      <td class="action-buttons">
        <button class="btn btn-sm btn-outline-primary" onclick="editCategory('${category.id}')">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory('${category.id}')">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    `;
    tableBody.appendChild(row);
  });
}

function editCategory(id) {
  const categories = getCategories();
  const category = categories.find(cat => cat.id === id);
  
  if (category) {
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categorySlug').value = category.slug;
    document.getElementById('categoryDescription').value = category.description || '';
    
    document.getElementById('categoryModalTitle').textContent = 'Editar Categoria';
    
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
  }
}

function deleteCategory(id) {
  if (confirm('Tem certeza que deseja excluir esta categoria?')) {
    const categories = getCategories();
    const filteredCategories = categories.filter(cat => cat.id !== id);
    localStorage.setItem('blogCategories', JSON.stringify(filteredCategories));
    
    loadCategoriesTable();
    loadCategoriesForSelect();
    loadDashboard();
  }
}

function generateSlug() {
  const name = document.getElementById('categoryName').value;
  const slug = name.toLowerCase().replace(/\s+/g, '-');
  document.getElementById('categorySlug').value = slug;
}

// Funções auxiliares
function getPosts() {
  const posts = localStorage.getItem('blogPosts');
  return posts ? JSON.parse(posts) : [];
}

function getCategories() {
  const categories = localStorage.getItem('blogCategories');
  
  // Se não existirem categorias, criar algumas padrão
  if (!categories) {
    const defaultCategories = [
      { id: '1', name: 'Marketing Digital', slug: 'marketing-digital', description: 'Artigos sobre estratégias de marketing digital' },
      { id: '2', name: 'Design', slug: 'design', description: 'Artigos sobre design e UX/UI' },
      { id: '3', name: 'Tecnologia', slug: 'tecnologia', description: 'Artigos sobre tecnologia e desenvolvimento' },
      { id: '4', name: 'E-commerce', slug: 'e-commerce', description: 'Artigos sobre comércio eletrônico' },
      { id: '5', name: 'SEO', slug: 'seo', description: 'Artigos sobre otimização para mecanismos de busca' }
    ];
    localStorage.setItem('blogCategories', JSON.stringify(defaultCategories));
    return defaultCategories;
  }
  
  return JSON.parse(categories);
}

function loadCategoriesForSelect() {
  const categories = getCategories();
  const selectElement = document.getElementById('postCategory');
  const filterSelect = document.getElementById('filterCategory');
  
  // Limpar opções existentes (exceto a primeira)
  while (selectElement.children.length > 1) {
    selectElement.removeChild(selectElement.lastChild);
  }
  
  while (filterSelect.children.length > 1) {
    filterSelect.removeChild(filterSelect.lastChild);
  }
  
  // Adicionar categorias
  categories.forEach(category => {
    const option = document.createElement('option');
    option.value = category.name;
    option.textContent = category.name;
    selectElement.appendChild(option);
    
    const filterOption = document.createElement('option');
    filterOption.value = category.name;
    filterOption.textContent = category.name;
    filterSelect.appendChild(filterOption);
  });
}

function loadDashboard() {
  const posts = getPosts();
  const categories = getCategories();
  
  document.getElementById('totalPosts').textContent = posts.length;
  document.getElementById('publishedPosts').textContent = posts.filter(post => post.status === 'published').length;
  document.getElementById('draftPosts').textContent = posts.filter(post => post.status === 'draft').length;
  document.getElementById('totalCategories').textContent = categories.length;
}

function previewImage() {
  const file = document.getElementById('postImage').files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('imagePreview').src = e.target.result;
      document.getElementById('imagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR');
}