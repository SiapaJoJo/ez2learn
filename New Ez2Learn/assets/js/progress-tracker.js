/**
 * Progress Tracker - Client-side Offline Queue Handler
 * Implements recoverability for progress updates
 * Handles offline scenarios with automatic retry
 */

class ProgressTracker {
    constructor() {
        this.queueKey = 'ez2learn_progress_queue';
        this.retryAttempts = 3;
        this.retryDelay = 2000; // 2 seconds
        this.toastDuration = 3000; // 3 seconds
        this.animationDuration = 800; // 0.8 seconds
        
        // Initialize
        this.init();
    }
    
    init() {
        // Process queue on page load
        this.processQueue();
        
        // Listen for online event
        window.addEventListener('online', () => {
            console.log('Connection restored, processing queue...');
            this.processQueue();
        });
        
        // Periodic queue check (every 10 seconds)
        setInterval(() => {
            if (navigator.onLine) {
                this.processQueue();
            }
        }, 10000);
    }
    
    /**
     * Mark material as complete
     * @param {number} materialId 
     * @param {Function} successCallback 
     * @param {Function} errorCallback 
     */
    async markMaterialComplete(materialId, successCallback = null, errorCallback = null) {
        const event = {
            type: 'material_complete',
            material_id: materialId,
            timestamp: Date.now(),
            attempts: 0
        };
        
        // Try to send immediately
        const success = await this.sendEvent(event);
        
        if (success) {
            this.showToast('Material marked complete! Progress updated.', 'success');
            if (successCallback) successCallback(event);
        } else {
            // Queue for retry
            this.addToQueue(event);
            this.showToast('Saved offline. Will sync when connection returns.', 'warning');
            if (errorCallback) errorCallback(event);
        }
    }
    
    /**
     * Send event to server
     * @param {Object} event 
     * @returns {Promise<boolean>}
     */
    async sendEvent(event) {
        event.attempts = (event.attempts || 0) + 1;
        
        try {
            // Use relative path from current page
            const apiPath = window.location.pathname.includes('/student/') 
                ? '../../api/progress/mark-material-complete.php'
                : '/Ez2Learn/New Ez2Learn/api/progress/mark-material-complete.php';
                
            const response = await fetch(apiPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ material_id: event.material_id }),
                signal: AbortSignal.timeout(5000) // 5 second timeout
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI with new progress
                this.updateProgressUI(data.completed_percentage, data.breakdown);
                return true;
            } else {
                console.error('Server returned error:', data.message);
                return false;
            }
        } catch (error) {
            console.error('Failed to send event:', error);
            return false;
        }
    }
    
    /**
     * Add event to offline queue
     * @param {Object} event 
     */
    addToQueue(event) {
        let queue = this.getQueue();
        
        // Check if already queued
        const exists = queue.find(e => e.type === event.type && e.material_id === event.material_id);
        if (exists) {
            return; // Already queued
        }
        
        queue.push(event);
        localStorage.setItem(this.queueKey, JSON.stringify(queue));
        console.log('Event queued:', event);
    }
    
    /**
     * Get offline queue
     * @returns {Array}
     */
    getQueue() {
        try {
            const queueStr = localStorage.getItem(this.queueKey);
            return queueStr ? JSON.parse(queueStr) : [];
        } catch (e) {
            console.error('Failed to parse queue:', e);
            return [];
        }
    }
    
    /**
     * Process offline queue
     */
    async processQueue() {
        if (!navigator.onLine) {
            return; // Don't process if offline
        }
        
        let queue = this.getQueue();
        
        if (queue.length === 0) {
            return; // Nothing to process
        }
        
        console.log(`Processing ${queue.length} queued events...`);
        const remaining = [];
        
        for (const event of queue) {
            // Skip if exceeded retry attempts
            if (event.attempts >= this.retryAttempts) {
                console.warn('Event exceeded retry attempts:', event);
                continue;
            }
            
            const success = await this.sendEvent(event);
            
            if (success) {
                console.log('Successfully sent queued event:', event);
            } else {
                // Keep in queue for next retry
                remaining.push(event);
            }
            
            // Small delay between retries
            await this.sleep(500);
        }
        
        // Update queue
        localStorage.setItem(this.queueKey, JSON.stringify(remaining));
        
        if (remaining.length === 0) {
            this.showToast('All progress synced successfully!', 'success');
        } else {
            console.log(`${remaining.length} events still pending`);
        }
    }
    
    /**
     * Update progress UI with animation
     * @param {number} percentage 
     * @param {Object} breakdown 
     */
    updateProgressUI(percentage, breakdown = null) {
        // Update progress bar
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.transition = `width ${this.animationDuration}ms ease-out`;
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            progressBar.textContent = percentage + '%';
        }
        
        // Update percentage text
        const progressText = document.querySelector('.progress-percentage');
        if (progressText) {
            this.animateNumber(progressText, percentage);
        }
        
        // Update breakdown if provided
        if (breakdown) {
            this.updateBreakdownUI(breakdown);
        }
        
        // Check for certificate eligibility
        if (percentage >= 100) {
            this.showCertificateEligible();
        }
    }
    
    /**
     * Animate number change
     * @param {HTMLElement} element 
     * @param {number} target 
     */
    animateNumber(element, target) {
        const current = parseInt(element.textContent) || 0;
        const increment = target > current ? 1 : -1;
        const duration = this.animationDuration;
        const steps = Math.abs(target - current);
        const stepDuration = duration / steps;
        
        let value = current;
        const interval = setInterval(() => {
            value += increment;
            element.textContent = value + '%';
            
            if (value === target) {
                clearInterval(interval);
            }
        }, stepDuration);
    }
    
    /**
     * Update breakdown UI
     * @param {Object} breakdown 
     */
    updateBreakdownUI(breakdown) {
        if (breakdown.materials) {
            const matEl = document.querySelector('.materials-progress');
            if (matEl) matEl.textContent = `${breakdown.materials.completed}/${breakdown.materials.total}`;
        }
        if (breakdown.assignments) {
            const assEl = document.querySelector('.assignments-progress');
            if (assEl) assEl.textContent = `${breakdown.assignments.completed}/${breakdown.assignments.total}`;
        }
        if (breakdown.quizzes) {
            const quizEl = document.querySelector('.quizzes-progress');
            if (quizEl) quizEl.textContent = `${breakdown.quizzes.completed}/${breakdown.quizzes.total}`;
        }
    }
    
    /**
     * Show certificate eligible notification
     */
    showCertificateEligible() {
        const certBadge = document.querySelector('.certificate-badge');
        if (certBadge) {
            certBadge.classList.remove('d-none');
            certBadge.classList.add('animate-bounce');
        }
    }
    
    /**
     * Show toast notification
     * @param {string} message 
     * @param {string} type success|warning|error
     */
    showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.progress-toast');
        existingToasts.forEach(t => t.remove());
        
        // Create toast
        const toast = document.createElement('div');
        toast.className = `progress-toast toast-${type}`;
        toast.textContent = message;
        
        // Style
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.padding = '15px 20px';
        toast.style.borderRadius = '8px';
        toast.style.zIndex = '9999';
        toast.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        toast.style.fontWeight = '500';
        toast.style.animation = 'slideInRight 0.3s ease-out';
        
        // Color based on type
        if (type === 'success') {
            toast.style.backgroundColor = '#28a745';
            toast.style.color = '#fff';
        } else if (type === 'warning') {
            toast.style.backgroundColor = '#ffc107';
            toast.style.color = '#000';
        } else if (type === 'error') {
            toast.style.backgroundColor = '#dc3545';
            toast.style.color = '#fff';
        } else {
            toast.style.backgroundColor = '#17a2b8';
            toast.style.color = '#fff';
        }
        
        document.body.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, this.toastDuration);
    }
    
    /**
     * Sleep helper
     * @param {number} ms 
     * @returns {Promise}
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes animate-bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .animate-bounce {
        animation: animate-bounce 0.6s ease-in-out 3;
    }
`;
document.head.appendChild(style);

// Initialize global instance
window.progressTracker = new ProgressTracker();
