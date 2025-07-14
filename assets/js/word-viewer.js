/**
 * Word Document Viewer
 * Provides a Microsoft Word-like interface for viewing and commenting on documents
 */

class WordViewer {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container with ID '${containerId}' not found`);
            return;
        }
        
        this.options = {
            showComments: true,
            showToolbar: true,
            allowZoom: false,
            ...options
        };
        
        this.currentFile = null;
        this.content = null;
        this.zoomLevel = 100;
        this.comments = [];
        
        this.init();
    }
    
    init() {
        this.createViewer();
        this.bindEvents();
    }
    
    createViewer() {
        console.log(`[WordViewer] createViewer() called for container: ${this.containerId}`);
        console.log(`[WordViewer] Container element:`, this.container);
        
        this.container.innerHTML = `
            <div class="word-viewer">
                <div class="word-document">
                    <div class="word-page">
                        <div class="word-content" id="${this.containerId}-content">
                            <div class="word-loading">
                                <div class="spinner"></div>
                                <p>Loading document...</p>
                                <div class="loading-steps" style="margin-top: 12px; font-size: 12px; color: #9ca3af;">
                                    <div>Fetching document content</div>
                                    <div style="margin-top: 4px;">Processing Word document structure</div>
                                    <div style="margin-top: 4px;">Preparing for display</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ${this.options.allowZoom ? this.createZoomControls() : ''}
            </div>
        `;
        
        console.log(`[WordViewer] Viewer HTML created, content div ID: ${this.containerId}-content`);
        
        // Verify the content div was created
        const contentDiv = document.getElementById(`${this.containerId}-content`);
        console.log(`[WordViewer] Content div verification:`, contentDiv);
        
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
        const maxRetries = 3;
        let attempt = 0;
        
        const attemptLoad = async () => {
            attempt++;
            try {
                console.log(`[WordViewer] Attempt ${attempt}/${maxRetries} - Starting load for file ID: ${fileId} in container: ${this.containerId}`);
                console.log(`[WordViewer] Container element:`, this.container);
                
                this.showLoading();
                console.log(`[WordViewer] Loading state displayed`);
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => {
                    console.log(`[WordViewer] Request timed out after 15 seconds (attempt ${attempt})`);
                    controller.abort();
                }, 15000); // Reduced timeout to 15 seconds
                
                console.log(`[WordViewer] Starting fetch request to: api/extract_document_content.php?file_id=${fileId}`);
                
                const response = await fetch(`api/extract_document_content.php?file_id=${fileId}`, {
                    signal: controller.signal,
                    credentials: 'same-origin',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                console.log(`[WordViewer] Fetch completed, response status: ${response.status}`);
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                console.log(`[WordViewer] Parsing JSON response...`);
                const data = await response.json();
                console.log(`[WordViewer] Response data:`, data);
                
                if (data.success) {
                    console.log(`[WordViewer] Setting file info and content...`);
                    this.currentFile = data.file_info;
                    this.content = data.content;
                    
                    // Check if this is a server limitation response
                    if (data.server_limitation) {
                        console.log(`[WordViewer] Server limitation detected, showing limitation message`);
                        this.displayServerLimitationMessage();
                    } else {
                        console.log(`[WordViewer] Document content loaded successfully, ${this.content.length} items`);
                        this.displayDocument();
                    }
                    
                    console.log(`[WordViewer] Updating toolbar...`);
                    this.updateToolbar();
                    console.log(`[WordViewer] Load process completed successfully`);
                    return true; // Success
                } else {
                    console.error(`[WordViewer] Document load failed:`, data.error);
                    throw new Error(data.error || 'Failed to load document');
                }
            } catch (error) {
                console.error(`[WordViewer] Error in attempt ${attempt}:`, error);
                
                if (error.name === 'AbortError') {
                    console.log(`[WordViewer] Request was aborted due to timeout (attempt ${attempt})`);
                    if (attempt < maxRetries) {
                        console.log(`[WordViewer] Retrying in 2 seconds...`);
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        return attemptLoad(); // Retry
                    } else {
                        this.showError('Document loading timed out after multiple attempts. Please check your network connection and try again.');
                    }
                } else {
                    if (attempt < maxRetries) {
                        console.log(`[WordViewer] Retrying in 1 second...`);
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        return attemptLoad(); // Retry
                    } else {
                        this.showError('Error loading document: ' + error.message);
                    }
                }
                return false; // Failure
            }
        };
        
        return attemptLoad();
    }
    
    displayServerLimitationMessage() {
        const contentDiv = document.getElementById(`${this.containerId}-content`);
        
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
        console.log(`[WordViewer] displayDocument() called for container: ${this.containerId}`);
        const contentDiv = document.getElementById(`${this.containerId}-content`);
        
        if (!contentDiv) {
            console.error(`[WordViewer] Content div not found: ${this.containerId}-content`);
            console.log(`[WordViewer] Available elements:`, document.querySelectorAll('[id*="content"]'));
            // Try alternative content div names
            const altContentDiv = document.getElementById(this.containerId);
            if (altContentDiv) {
                console.log(`[WordViewer] Using alternative container directly`);
                altContentDiv.innerHTML = this.createDocumentHTML();
                this.bindParagraphEvents();
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                return;
            }
            return;
        }
        
        try {
            console.log(`[WordViewer] Content array:`, this.content);
            
            if (!this.content || this.content.length === 0) {
                console.log(`[WordViewer] No content available, showing empty state`);
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
            
            const html = this.createDocumentHTML();
            console.log(`[WordViewer] Generated HTML length:`, html.length);
            
            if (!html.trim()) {
                console.log(`[WordViewer] Empty HTML generated, showing error`);
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
            
            console.log(`[WordViewer] Setting innerHTML...`);
            contentDiv.innerHTML = html;
            
            console.log(`[WordViewer] Binding paragraph events...`);
            this.bindParagraphEvents();
            
            console.log(`[WordViewer] Initializing Lucide icons...`);
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            console.log(`[WordViewer] Auto-scrolling to top...`);
            const wordPage = document.querySelector('.word-page');
            if (wordPage) {
                wordPage.scrollTop = 0;
            }
            
            console.log(`[WordViewer] Document content displayed successfully`);
        } catch (error) {
            console.error(`[WordViewer] Error in displayDocument:`, error);
            contentDiv.innerHTML = `
                <div class="word-error">
                    <i data-lucide="alert-triangle" class="word-error-icon"></i>
                    <h3>Display Error</h3>
                    <p>Error displaying document: ${error.message}</p>
                    <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Reload Page</button>
                </div>
            `;
            lucide.createIcons();
        }
    }
    
    createDocumentHTML() {
        let html = '';
        console.log(`[WordViewer] Creating HTML from ${this.content.length} content items`);
        
        this.content.forEach((item, index) => {
            const paragraphId = `para_${index + 1}`;
            let cssClass = 'word-paragraph';
            let content = item.content;
            
            // Skip empty or very short content that might be artifacts
            if (!content || content.trim().length < 3) {
                console.log(`[WordViewer] Skipping empty content at index ${index}`);
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
        
        console.log(`[WordViewer] Generated HTML with ${html.split('<div class="word-paragraph').length - 1} paragraphs`);
        return html;
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
        if (typeof window.openParagraphCommentModal === 'function') {
            window.openParagraphCommentModal(paragraphId, paragraphContent);
        } else if (typeof openParagraphCommentModal === 'function') {
            // Fallback for legacy function
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
    
    openHighlightCommentModal(highlightId, highlightedText) {
        // Fallback modal for commenting on highlights when global function isn't available
        const existingModal = document.getElementById('word-highlight-comment-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modal = document.createElement('div');
        modal.id = 'word-highlight-comment-modal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">Comment on Highlight</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Highlighted Text:</label>
                    <div class="p-2 bg-yellow-100 rounded text-sm max-h-32 overflow-y-auto border border-yellow-300">
                        <mark style="background-color: #fef3c7; padding: 2px 4px; border-radius: 3px;">${highlightedText}</mark>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="word-highlight-comment-text" class="block text-sm font-medium text-gray-700 mb-2">Comment:</label>
                    <textarea id="word-highlight-comment-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your comment about this highlight..."></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button id="word-cancel-highlight-comment" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button id="word-save-highlight-comment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Comment</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Add event listeners
        document.getElementById('word-cancel-highlight-comment').addEventListener('click', () => {
            modal.remove();
        });
        
        document.getElementById('word-save-highlight-comment').addEventListener('click', () => {
            const commentText = document.getElementById('word-highlight-comment-text').value.trim();
            if (commentText) {
                this.saveHighlightComment(highlightId, commentText);
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
    
    saveHighlightComment(highlightId, commentText) {
        if (!window.currentChapterId) {
            console.error('No current chapter ID');
            if (typeof showNotification === 'function') {
                showNotification('Error: No chapter selected', 'error');
            }
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('chapter_id', window.currentChapterId);
        formData.append('comment_text', commentText);
        formData.append('highlight_id', highlightId);
        
        console.log('Saving highlight comment with data:', {
            chapter_id: window.currentChapterId,
            comment_text: commentText,
            highlight_id: highlightId
        });
        
        const url = 'api/comments.php';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', e);
                    throw new Error(`Invalid JSON response: ${text.substring(0, 200)}${text.length > 200 ? '...' : ''}`);
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Add visual indicator to the highlight
                const highlightElement = document.querySelector(`[data-highlight-id="${highlightId}"]`);
                if (highlightElement) {
                    highlightElement.classList.add('has-comment');
                    highlightElement.title = highlightElement.title + ' (Has comments)';
                }
                
                // Show success notification
                if (typeof showNotification === 'function') {
                    showNotification('Comment added to highlight successfully', 'success');
                }
            } else {
                const errorMessage = data.error || data.message || 'Unknown error occurred';
                console.error('Failed to save highlight comment:', errorMessage);
                if (typeof showNotification === 'function') {
                    showNotification('Failed to add comment to highlight: ' + errorMessage, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error saving highlight comment:', error);
            if (typeof showNotification === 'function') {
                showNotification('Error adding comment to highlight: ' + error.message, 'error');
            }
        });
    }

    saveComment(paragraphId, commentText) {
        if (!window.currentChapterId) {
            console.error('No current chapter ID');
            if (typeof showNotification === 'function') {
                showNotification('Error: No chapter selected', 'error');
            }
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
        
        const url = 'api/comments.php';
        console.log('Making request to URL:', url);
        console.log('FormData contents:');
        for (const pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
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
                // Mark paragraph as commented
                const paragraph = document.getElementById(paragraphId);
                if (paragraph) {
                    paragraph.classList.add('commented');
                    const indicator = paragraph.querySelector('.comment-indicator');
                    if (indicator) {
                        indicator.style.display = 'flex';
                    }
                }
                
                // Reload comments in sidebar if function exists
                if (typeof loadComments === 'function') {
                    loadComments(window.currentChapterId);
                }
                
                // Show success notification
                if (typeof showNotification === 'function') {
                    showNotification('Comment added successfully', 'success');
                }
            } else {
                const errorMessage = data.error || data.message || 'Unknown error occurred';
                console.error('Failed to save comment:', errorMessage);
                if (typeof showNotification === 'function') {
                    showNotification('Failed to add comment: ' + errorMessage, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error saving comment:', error);
            if (typeof showNotification === 'function') {
                showNotification('Error adding comment: ' + error.message, 'error');
            }
        });
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
        
        fetch('api/comments.php', {
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
                        
                        // Add click handler for commenting on highlights
                        highlightSpan.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            if (typeof window.openHighlightCommentModal === 'function') {
                                window.openHighlightCommentModal(data.highlight_id, window.selectedText, window.currentChapterId);
                            } else {
                                // Fallback modal
                                this.openHighlightCommentModal(data.highlight_id, window.selectedText);
                            }
                        });
                        
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
                
                // Exit highlight mode
                window.isHighlightMode = false;
                const highlightBtn = document.getElementById('highlight-btn');
                if (highlightBtn) {
                    highlightBtn.textContent = 'Highlight';
                    highlightBtn.className = 'px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-sm hover:bg-yellow-200';
                }
                
                // Show success notification
                if (typeof showNotification === 'function') {
                    showNotification('Text highlighted successfully', 'success');
                }
            } else {
                const errorMessage = data.error || data.message || 'Unknown error occurred';
                console.error('Failed to add highlight:', errorMessage);
                if (typeof showNotification === 'function') {
                    showNotification('Failed to add highlight: ' + errorMessage, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error adding highlight:', error);
            if (typeof showNotification === 'function') {
                showNotification('Error adding highlight: ' + error.message, 'error');
            }
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
        console.log(`[WordViewer] showLoading() called for container: ${this.containerId}`);
        const contentDiv = document.getElementById(`${this.containerId}-content`);
        
        if (!contentDiv) {
            console.error(`[WordViewer] Content div not found: ${this.containerId}-content`);
            return;
        }
        
        console.log(`[WordViewer] Setting loading HTML in content div`);
        contentDiv.innerHTML = `
            <div class="word-loading">
                <div class="spinner"></div>
                <p>Loading document...</p>
                <div class="loading-steps" style="margin-top: 12px; font-size: 12px; color: #9ca3af;">
                    <div>Fetching document content</div>
                    <div style="margin-top: 4px;">Processing Word document structure</div>
                    <div style="margin-top: 4px;">Preparing for display</div>
                </div>
            </div>
        `;
        console.log(`[WordViewer] Loading HTML set successfully`);
    }
    
    showError(message) {
        const contentDiv = document.getElementById(`${this.containerId}-content`);
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

// Global debug and manual reload functions
window.debugWordViewer = function(containerId) {
    console.log('[Debug] === WORD VIEWER DEBUG ===');
    console.log('[Debug] Container ID:', containerId || 'not specified');
    
    if (containerId) {
        const container = document.getElementById(containerId);
        console.log('[Debug] Container element:', container);
        
        if (container) {
            const contentDiv = document.getElementById(containerId + '-content');
            console.log('[Debug] Content div:', contentDiv);
            console.log('[Debug] Content HTML:', contentDiv ? contentDiv.innerHTML : 'not found');
        }
    }
    
    console.log('[Debug] Available global variables:');
    console.log('[Debug] - window.currentFileId:', window.currentFileId);
    console.log('[Debug] - window.currentChapterId:', window.currentChapterId);
    console.log('[Debug] - fullscreenWordViewer:', typeof fullscreenWordViewer !== 'undefined' ? fullscreenWordViewer : 'not defined');
    console.log('[Debug] - wordViewer:', typeof wordViewer !== 'undefined' ? wordViewer : 'not defined');
};

window.forceReloadDocument = function(fileId, containerId) {
    console.log('[Force Reload] Starting manual reload...');
    console.log('[Force Reload] File ID:', fileId);
    console.log('[Force Reload] Container ID:', containerId);
    
    if (!fileId) {
        fileId = window.currentFileId;
        console.log('[Force Reload] Using global file ID:', fileId);
    }
    
    if (!fileId) {
        console.error('[Force Reload] No file ID available');
        alert('No file ID available. Please select a chapter first.');
        return;
    }
    
    if (!containerId) {
        containerId = 'fullscreen-document-content';
        console.log('[Force Reload] Using default container ID:', containerId);
    }
    
    try {
        const viewer = new WordViewer(containerId, {
            showComments: true,
            showToolbar: false,
            allowZoom: true
        });
        
        console.log('[Force Reload] Created viewer:', viewer);
        
        viewer.loadDocument(fileId)
            .then(() => {
                console.log('[Force Reload] Document loaded successfully');
                alert('Document reloaded successfully!');
            })
            .catch(error => {
                console.error('[Force Reload] Error loading document:', error);
                alert('Error reloading document: ' + error.message);
            });
            
    } catch (error) {
        console.error('[Force Reload] Error creating viewer:', error);
        alert('Error creating viewer: ' + error.message);
    }
};

// Export for use in other scripts
window.WordViewer = WordViewer; 