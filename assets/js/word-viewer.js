/**
 * Word Document Viewer
 * Provides a Microsoft Word-like interface for viewing and commenting on documents
 */

class WordViewer {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            showComments: true,
            showToolbar: true,
            allowZoom: true,
            ...options
        };
        
        this.zoomLevel = 100;
        this.currentFile = null;
        this.content = [];
        this.comments = [];
        
        this.init();
    }
    
    init() {
        this.createViewer();
        this.bindEvents();
    }
    
    createViewer() {
        this.container.innerHTML = `
            <div class="word-viewer">
                <div class="word-document">
                    <div class="word-page">
                        <div class="word-content" id="word-content">
                            <div class="word-loading">
                                <div class="spinner"></div>
                                Loading document...
                            </div>
                        </div>
                    </div>
                </div>
                ${this.options.allowZoom ? this.createZoomControls() : ''}
            </div>
        `;
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    createToolbar() {
        return `
            <div class="word-toolbar">
                <div class="word-toolbar-left">
                    <div class="word-document-title" id="document-title">Document</div>
                    <div class="word-document-info" id="document-info">Loading...</div>
                </div>
                <div class="word-toolbar-right">
                    <span id="page-info">Page 1 of 1</span>
                </div>
            </div>
        `;
    }
    

    
    createZoomControls() {
        return `
            <div class="word-zoom-controls">
                <button class="zoom-btn" id="zoom-out" title="Zoom Out">
                    <i data-lucide="minus" class="w-3 h-3"></i>
                </button>
                <span class="zoom-level" id="zoom-level">100%</span>
                <button class="zoom-btn" id="zoom-in" title="Zoom In">
                    <i data-lucide="plus" class="w-3 h-3"></i>
                </button>
            </div>
        `;
    }
    
    bindEvents() {
        // Zoom controls
        if (this.options.allowZoom) {
            const zoomIn = document.getElementById('zoom-in');
            const zoomOut = document.getElementById('zoom-out');
            
            if (zoomIn) zoomIn.addEventListener('click', () => this.zoom(10));
            if (zoomOut) zoomOut.addEventListener('click', () => this.zoom(-10));
        }
        
        // Initialize Lucide icons after events
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    async loadDocument(fileId) {
        try {
            this.showLoading();
            
            const response = await fetch(`api/extract_document_content.php?file_id=${fileId}`);
            const data = await response.json();
            
            if (data.success) {
                this.currentFile = data.file_info;
                this.content = data.content;
                
                // Check if this is a server limitation response
                if (data.server_limitation) {
                    this.displayServerLimitationMessage();
                } else {
                    this.displayDocument();
                }
                this.updateToolbar();
            } else {
                this.showError(data.error || 'Failed to load document');
            }
        } catch (error) {
            console.error('Error loading document:', error);
            this.showError('Error loading document: ' + error.message);
        }
    }
    
    displayServerLimitationMessage() {
        const contentDiv = document.getElementById('word-content');
        
        contentDiv.innerHTML = `
            <div class="word-server-limitation">
                <div class="limitation-header">
                    <i data-lucide="info" class="limitation-icon"></i>
                    <h3>Document Viewer Not Fully Available</h3>
                </div>
                <div class="limitation-content">
                    <div class="limitation-message">
                        <p><strong>Server Configuration Issue:</strong> The Word document viewer requires the PHP ZipArchive extension, which is not currently installed on this server.</p>
                        <p>This means we cannot extract and display the document content directly in the browser.</p>
                    </div>
                    
                    <div class="file-info-card">
                        <div class="file-icon">
                            <i data-lucide="file-text" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <div class="file-details">
                            <h4>${this.currentFile ? this.escapeHtml(this.currentFile.name) : 'Document'}</h4>
                            <p class="file-type">Microsoft Word Document</p>
                            <p class="file-size">${this.formatFileSize()}</p>
                            <p class="upload-date">Uploaded: ${this.currentFile ? new Date(this.currentFile.uploaded_at).toLocaleDateString() : 'Unknown'}</p>
                        </div>
                    </div>
                    
                    <div class="available-options">
                        <h4>Available Options:</h4>
                        <div class="option-buttons">
                            <button class="option-btn primary" onclick="this.closest('.word-viewer').querySelector('#download-btn').click()">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Download Document
                            </button>
                            <button class="option-btn secondary" onclick="window.print()">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                                Print This Page
                            </button>
                        </div>
                    </div>
                    
                    <div class="limitations-note">
                        <h4>What You Can Still Do:</h4>
                        <ul>
                            <li>Download the document to view it locally</li>
                            <li>Add general comments about the chapter</li>
                            <li>Provide feedback through the feedback system</li>
                            <li>Track submission status and progress</li>
                        </ul>
                    </div>
                    
                    <div class="admin-note">
                        <p><strong>For System Administrators:</strong> To enable full document viewing, install the PHP ZipArchive extension (usually part of php-zip package).</p>
                    </div>
                </div>
            </div>
        `;
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    formatFileSize() {
        if (!this.currentFile) return 'Unknown size';
        
        // Get file size from the content if available
        const sizeMatch = this.content && this.content.find(item => 
            item.content && item.content.includes('bytes')
        );
        
        if (sizeMatch) {
            return sizeMatch.content.split('Size: ')[1] || 'Unknown size';
        }
        
        return 'Unknown size';
    }
    
    displayDocument() {
        const contentDiv = document.getElementById('word-content');
        
        if (!this.content || this.content.length === 0) {
            contentDiv.innerHTML = `
                <div class="word-error">
                    <i data-lucide="file-x" class="word-error-icon"></i>
                    <h3>No Content Available</h3>
                    <p>No readable content could be extracted from this document.</p>
                    <p class="text-sm mt-2">Try downloading the document to view it in its original format.</p>
                </div>
            `;
            lucide.createIcons();
            return;
        }
        
        let html = '';
        this.content.forEach((item, index) => {
            const paragraphId = `para_${index + 1}`;
            let cssClass = 'word-paragraph';
            let content = item.content;
            
            // Skip empty or very short content that might be artifacts
            if (!content || content.trim().length < 3) {
                return;
            }
            
            // Determine paragraph type based on content
            if (item.type === 'heading' || content.match(/^(Chapter|CHAPTER)\s+\d+/i)) {
                cssClass += ' word-heading-1';
            } else if (content.match(/^\d+\.\d+\s+/) || content.match(/^[IVX]+\.\s+/)) {
                cssClass += ' word-heading-2';
            } else if (content.match(/^\d+\.\s+/) || content.match(/^[A-Z][a-z]+:/) || 
                      (content.length < 100 && content.match(/^[A-Z][A-Z\s]+$/))) {
                cssClass += ' word-heading-3';
            }
            
            // Clean up content for better display
            content = this.formatParagraphContent(content);
            
            html += `
                <div class="${cssClass}" id="${paragraphId}" data-paragraph-id="${paragraphId}">
                    ${content}
                    <div class="comment-indicator" style="display: none;">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                    </div>
                </div>
            `;
        });
        
        // If we still have no content after filtering, show error
        if (!html.trim()) {
            contentDiv.innerHTML = `
                <div class="word-error">
                    <i data-lucide="file-x" class="word-error-icon"></i>
                    <h3>Document Processing Issue</h3>
                    <p>The document content could not be properly processed for display.</p>
                    <p class="text-sm mt-2">Please try downloading the document to view it in its original format.</p>
                </div>
            `;
            lucide.createIcons();
            return;
        }
        
        contentDiv.innerHTML = html;
        
        // Add paragraph click handlers for commenting
        this.bindParagraphEvents();
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Auto-scroll to top
        const wordPage = document.querySelector('.word-page');
        if (wordPage) {
            wordPage.scrollTop = 0;
        }
    }
    
    formatParagraphContent(content) {
        // Remove extra whitespace and normalize
        content = content.trim();
        content = content.replace(/\s+/g, ' ');
        
        // Handle special formatting
        if (content.match(/^(Abstract|Introduction|Methodology|Results|Discussion|Conclusion|References)/i)) {
            return `<strong>${this.escapeHtml(content)}</strong>`;
        }
        
        // Handle numbered lists
        if (content.match(/^\d+\./)) {
            return `<strong>${this.escapeHtml(content)}</strong>`;
        }
        
        // Handle bullet points
        if (content.match(/^[\u2022\u2023\u25E6\u2043\u2219]/)) {
            return `<span style="margin-left: 20px;">${this.escapeHtml(content)}</span>`;
        }
        
        // Regular paragraph content
        return this.escapeHtml(content);
    }
    
    bindParagraphEvents() {
        const paragraphs = document.querySelectorAll('.word-paragraph');
        paragraphs.forEach(paragraph => {
            // Show comment indicator on hover
            paragraph.addEventListener('mouseenter', () => {
                const indicator = paragraph.querySelector('.comment-indicator');
                if (indicator) indicator.style.display = 'flex';
            });
            
            paragraph.addEventListener('mouseleave', () => {
                const indicator = paragraph.querySelector('.comment-indicator');
                if (indicator && !paragraph.classList.contains('commented')) {
                    indicator.style.display = 'none';
                }
            });
            
            // Handle paragraph clicks for commenting
            paragraph.addEventListener('click', (e) => {
                if (e.target.closest('.comment-indicator')) {
                    this.showParagraphComments(paragraph.dataset.paragraphId);
                } else {
                    // If in highlight mode, highlight the paragraph
                    if (window.isHighlightMode) {
                        this.highlightParagraph(paragraph.dataset.paragraphId);
                    } else {
                        // Open comment modal for the paragraph
                        this.openCommentModal(paragraph.dataset.paragraphId, paragraph.textContent);
                    }
                }
            });
            
            // Handle text selection for highlighting
            paragraph.addEventListener('mouseup', (e) => {
                const selection = window.getSelection();
                if (selection.toString().trim().length > 0 && window.isHighlightMode) {
                    window.selectedText = selection.toString().trim();
                    window.selectedRange = selection.getRangeAt(0);
                    window.selectedParagraphId = paragraph.dataset.paragraphId;
                    this.addTextHighlight();
                }
            });
        });
    }
    
    openCommentModal(paragraphId, paragraphContent) {
        // Check if we're in the context of the main document review page
        if (typeof openParagraphCommentModal === 'function') {
            openParagraphCommentModal(paragraphId, paragraphContent);
        } else {
            // Fallback: create a simple comment modal
            this.createSimpleCommentModal(paragraphId, paragraphContent);
        }
    }
    
    createSimpleCommentModal(paragraphId, paragraphContent) {
        // Remove existing modal if any
        const existingModal = document.getElementById('word-comment-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'word-comment-modal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">Add Comment</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Paragraph:</label>
                    <div class="p-2 bg-gray-100 rounded text-sm max-h-32 overflow-y-auto">${paragraphContent.substring(0, 300)}${paragraphContent.length > 300 ? '...' : ''}</div>
                </div>
                <div class="mb-4">
                    <label for="word-comment-text" class="block text-sm font-medium text-gray-700 mb-2">Comment:</label>
                    <textarea id="word-comment-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your comment..."></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button id="word-cancel-comment" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button id="word-save-comment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Comment</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Add event listeners
        document.getElementById('word-cancel-comment').addEventListener('click', () => {
            modal.remove();
        });
        
        document.getElementById('word-save-comment').addEventListener('click', () => {
            const commentText = document.getElementById('word-comment-text').value.trim();
            if (commentText) {
                this.saveComment(paragraphId, commentText);
                modal.remove();
            }
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    saveComment(paragraphId, commentText) {
        if (!window.currentChapterId) {
            console.error('No current chapter ID');
            this.showNotification('Error: No chapter selected', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('chapter_id', window.currentChapterId);
        formData.append('comment_text', commentText);
        formData.append('paragraph_id', paragraphId);
        
        console.log('Saving comment with data:', {
            chapter_id: window.currentChapterId,
            comment_text: commentText,
            paragraph_id: paragraphId
        });
        
        // Use relative URL for API calls
        const url = window.location.pathname.includes('systemFunda.php') ? 'api/comments.php' : '../api/comments.php';
        console.log('Making request to URL:', url);
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.text().then(text => {
                console.log('Raw response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', e);
                    throw new Error(`Invalid JSON response: ${text.substring(0, 200)}${text.length > 200 ? '...' : ''}`);
                }
            });
        })
        .then(data => {
            console.log('Parsed response data:', data);
            
            if (data.success) {
                // Mark paragraph as commented in all instances (regular and fullscreen)
                const paragraphs = document.querySelectorAll(`[id="${paragraphId}"]`);
                paragraphs.forEach(paragraph => {
                    paragraph.classList.add('commented');
                    const indicator = paragraph.querySelector('.comment-indicator');
                    if (indicator) {
                        indicator.style.display = 'flex';
                    }
                });
                
                // Reload comments in sidebar if function exists
                if (typeof loadComments === 'function') {
                    loadComments(window.currentChapterId);
                }
                
                // Show success notification
                this.showNotification('Comment added successfully', 'success');
            } else {
                const errorMessage = data.error || data.message || 'Unknown error occurred';
                console.error('Failed to save comment:', errorMessage);
                this.showNotification('Failed to add comment: ' + errorMessage, 'error');
            }
        })
        .catch(error => {
            console.error('Error saving comment:', error);
            this.showNotification('Error adding comment: ' + error.message, 'error');
        });
    }
    
    // Internal notification method for WordViewer
    showNotification(message, type = 'info') {
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            // Fallback notification system
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 text-white ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
    
    addTextHighlight() {
        if (!window.selectedText || !window.currentChapterId) {
            console.log('No selected text or chapter ID');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'add_highlight');
        formData.append('chapter_id', window.currentChapterId);
        formData.append('highlighted_text', window.selectedText);
        formData.append('highlight_color', window.currentHighlightColor || '#ffeb3b');
        formData.append('paragraph_id', window.selectedParagraphId);
        formData.append('start_offset', 0);
        formData.append('end_offset', window.selectedText.length);
        
        // Use relative URL for API calls
        const url = window.location.pathname.includes('systemFunda.php') ? 'api/comments.php' : '../api/comments.php';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Apply highlight visually
                if (window.selectedRange) {
                    try {
                        const highlightSpan = document.createElement('mark');
                        highlightSpan.style.backgroundColor = window.currentHighlightColor || '#ffeb3b';
                        highlightSpan.className = 'highlight-marker';
                        highlightSpan.dataset.highlightId = data.highlight_id;
                        
                        // Add context menu for removing highlights
                        highlightSpan.addEventListener('contextmenu', (e) => {
                            e.preventDefault();
                            if (confirm('Remove this highlight?')) {
                                this.removeHighlight(data.highlight_id);
                            }
                        });
                        
                        window.selectedRange.surroundContents(highlightSpan);
                    } catch (e) {
                        console.error('Error applying highlight:', e);
                    }
                }
                
                // Clear selection
                window.getSelection().removeAllRanges();
                window.selectedText = '';
                window.selectedRange = null;
                window.selectedParagraphId = null;
                
                // Exit highlight mode and update both regular and fullscreen buttons
                window.isHighlightMode = false;
                
                // Update regular highlight button
                const highlightBtn = document.getElementById('highlight-btn');
                if (highlightBtn) {
                    highlightBtn.textContent = 'Highlight';
                    highlightBtn.className = 'px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-sm hover:bg-yellow-200';
                }
                
                // Update fullscreen highlight button
                const fullscreenHighlightBtn = document.getElementById('fullscreen-highlight-btn');
                if (fullscreenHighlightBtn) {
                    fullscreenHighlightBtn.innerHTML = '<i data-lucide="highlighter" class="w-4 h-4 mr-2"></i>Highlight';
                    fullscreenHighlightBtn.className = 'toolbar-action-btn';
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
                
                document.body.style.cursor = 'default';
                
                // Show success notification
                this.showNotification('Text highlighted successfully', 'success');
            } else {
                const errorMessage = data.error || data.message || 'Unknown error occurred';
                console.error('Failed to add highlight:', errorMessage);
                this.showNotification('Failed to add highlight: ' + errorMessage, 'error');
            }
        })
        .catch(error => {
            console.error('Error adding highlight:', error);
            this.showNotification('Error adding highlight: ' + error.message, 'error');
        });
    }
    
    removeHighlight(highlightId) {
        const formData = new FormData();
        formData.append('action', 'remove_highlight');
        formData.append('highlight_id', highlightId);
        
        fetch('api/comments.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove highlight from DOM
                const highlightElement = document.querySelector(`[data-highlight-id="${highlightId}"]`);
                if (highlightElement) {
                    const parent = highlightElement.parentNode;
                    parent.insertBefore(document.createTextNode(highlightElement.textContent), highlightElement);
                    parent.removeChild(highlightElement);
                }
                
                if (typeof showNotification === 'function') {
                    showNotification('Highlight removed', 'success');
                }
            } else {
                console.error('Failed to remove highlight:', data.error);
            }
        })
        .catch(error => {
            console.error('Error removing highlight:', error);
        });
    }
    
    showParagraphComments(paragraphId) {
        // This would integrate with your existing comment system
        this.toggleComments();
        console.log('Show comments for paragraph:', paragraphId);
    }
    
    updateToolbar() {
        if (this.currentFile) {
            const titleElement = document.getElementById('document-title');
            const infoElement = document.getElementById('document-info');
            
            if (titleElement) {
                titleElement.textContent = this.currentFile.name;
            }
            
            if (infoElement) {
                const uploadDate = new Date(this.currentFile.uploaded_at).toLocaleDateString();
                infoElement.textContent = `Uploaded: ${uploadDate}`;
            }
        }
    }
    
    showLoading() {
        const contentDiv = document.getElementById('word-content');
        contentDiv.innerHTML = `
            <div class="word-loading">
                <div class="spinner"></div>
                Loading document...
            </div>
        `;
    }
    
    showError(message) {
        const contentDiv = document.getElementById('word-content');
        contentDiv.innerHTML = `
            <div class="word-error">
                <i data-lucide="alert-triangle" class="word-error-icon"></i>
                <p>${this.escapeHtml(message)}</p>
                <button onclick="location.reload()" style="margin-top: 12px; padding: 6px 12px; background: #4285f4; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Retry
                </button>
            </div>
        `;
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    zoom(delta) {
        this.zoomLevel = Math.max(50, Math.min(200, this.zoomLevel + delta));
        
        const document = this.container.querySelector('.word-document');
        if (document) {
            document.style.transform = `scale(${this.zoomLevel / 100})`;
            document.style.transformOrigin = 'top center';
        }
        
        const zoomLevelElement = document.getElementById('zoom-level');
        if (zoomLevelElement) {
            zoomLevelElement.textContent = `${this.zoomLevel}%`;
        }
    }
    

    
    print() {
        window.print();
    }
    
    download() {
        if (this.currentFile) {
            window.open(`api/download_file.php?file_id=${this.currentFile.id}`, '_blank');
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Public methods for external integration
    highlightParagraph(paragraphId) {
        const paragraph = document.getElementById(paragraphId);
        if (paragraph) {
            paragraph.classList.add('highlighted');
        }
    }
    
    addCommentToParagraph(paragraphId, comment) {
        const paragraph = document.getElementById(paragraphId);
        if (paragraph) {
            paragraph.classList.add('commented');
            const indicator = paragraph.querySelector('.comment-indicator');
            if (indicator) {
                indicator.style.display = 'flex';
            }
        }
    }
    
    loadComments(comments) {
        this.comments = comments;
        this.displayComments();
    }
    
    displayComments() {
        const sidebarContent = document.getElementById('sidebar-content');
        if (!sidebarContent) return;
        
        if (this.comments.length === 0) {
            sidebarContent.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p class="text-sm">No comments yet</p>
                </div>
            `;
        } else {
            let html = '';
            this.comments.forEach(comment => {
                html += `
                    <div class="comment-thread">
                        <div class="comment-author">${this.escapeHtml(comment.author)}</div>
                        <div class="comment-text">${this.escapeHtml(comment.text)}</div>
                        <div class="comment-time">${new Date(comment.created_at).toLocaleString()}</div>
                    </div>
                `;
            });
            sidebarContent.innerHTML = html;
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Export for use in other scripts
window.WordViewer = WordViewer; 