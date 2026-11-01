// Handle form submission for creating new posts
document.getElementById('postForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);

    const response = await fetch('add_post.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.json();

    if (result.status === 'success') {
        event.target.reset();
        loadPosts(); // Reload posts after new post is added
    } else {
        alert(result.message || 'Failed to post');
    }
});

// Function to load posts and display in the news feed
async function loadPosts() {
    const response = await fetch('get_posts.php');
    const result = await response.json();

    if (result.status === 'success') {
        const newsFeed = document.getElementById('newsFeed');
        newsFeed.innerHTML = '';

        result.posts.forEach(post => {
            const postElement = document.createElement('div');
            postElement.classList.add('post');
            postElement.innerHTML = `
                <p><strong>${post.username}</strong> <span>${new Date(post.created_at).toLocaleString()}</span></p>
                <p>${post.content}</p>
            `;
            newsFeed.appendChild(postElement);
        });
    } else {
        alert(result.message || 'Failed to load posts');
    }
}





