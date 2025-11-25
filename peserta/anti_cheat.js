// Anti-Cheat System Enhanced
class AntiCheatSystem {
    constructor() {
        this.violationCount = 0;
        this.maxViolations = 3;
        this.screenshotInterval = null;
        this.faceDetectionInterval = null;
        this.startTime = Date.now();
        
        this.init();
    }

    init() {
        this.setupTabSwitchDetection();
        this.setupKeyboardShortcuts();
        this.setupMouseDetection();
        this.setupScreenRecordingDetection();
        this.setupVirtualMachineDetection();
        this.setupFaceDetection();
        this.setupAutoScreenshot();
    }

    // Tab switch detection
    setupTabSwitchDetection() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.logViolation('Tab switch detected');
                this.showWarning('Jangan berpindah tab saat ujian berlangsung!');
            }
        });

        window.addEventListener('blur', () => {
            this.logViolation('Window blur detected');
            this.showWarning('Jangan minimize atau pindah aplikasi!');
        });
    }

    // Keyboard shortcuts blocking
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Block F12
            if (e.key === 'F12') {
                e.preventDefault();
                this.logViolation('F12 pressed');
            }
            
            // Block Ctrl+Shift+I/J/C
            if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
                e.preventDefault();
                this.logViolation('Dev tools shortcut pressed');
            }
            
            // Block Ctrl+U (view source)
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                this.logViolation('View source attempted');
            }
            
            // Block Alt+Tab, Alt+F4
            if (e.altKey && ['Tab', 'F4'].includes(e.key)) {
                e.preventDefault();
                this.logViolation('Alt+Tab or Alt+F4 pressed');
            }
            
            // Block Print Screen
            if (e.key === 'PrintScreen') {
                e.preventDefault();
                this.logViolation('PrintScreen pressed');
                navigator.clipboard.writeText('');
            }
        });

        // Block right click
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.logViolation('Right click attempted');
        });

        // Block copy/paste/select
        document.addEventListener('copy', (e) => {
            e.preventDefault();
            this.logViolation('Copy attempted');
        });

        document.addEventListener('paste', (e) => {
            e.preventDefault();
            this.logViolation('Paste attempted');
        });

        document.addEventListener('selectstart', (e) => {
            e.preventDefault();
            this.logViolation('Text selection attempted');
        });
    }

    // Mouse detection
    setupMouseDetection() {
        let mouseOutCount = 0;
        
        document.addEventListener('mouseleave', () => {
            mouseOutCount++;
            if (mouseOutCount > 5) {
                this.logViolation('Mouse left window multiple times');
            }
        });
    }

    // Screen recording detection
    setupScreenRecordingDetection() {
        // Detect screen sharing/recording via getDisplayMedia
        if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            const originalGetDisplayMedia = navigator.mediaDevices.getDisplayMedia;
            navigator.mediaDevices.getDisplayMedia = function(...args) {
                this.logViolation('Screen sharing/recording attempted');
                return Promise.reject(new Error('Screen sharing is not allowed during exam'));
            }.bind(this);
        }

        // Detect via screen capture API
        this.checkScreenRecordingInterval = setInterval(() => {
            this.detectScreenRecording();
        }, 5000);
    }

    async detectScreenRecording() {
        try {
            // Check for screen recording indicators
            const screenDetails = await window.getScreenDetails?.();
            if (screenDetails && screenDetails.currentScreen) {
                // Additional checks can be added here
            }
        } catch (error) {
            // Handle errors silently
        }
    }

    // Virtual machine detection
    setupVirtualMachineDetection() {
        this.checkVirtualMachine();
    }

    checkVirtualMachine() {
        // Check for VM indicators
        const vmIndicators = [
            'VirtualBox',
            'VMware',
            'Parallels',
            'QEMU',
            'Hyper-V'
        ];

        // Check user agent
        const userAgent = navigator.userAgent;
        for (const indicator of vmIndicators) {
            if (userAgent.includes(indicator)) {
                this.logViolation('Virtual machine detected: ' + indicator);
                break;
            }
        }

        // Check screen resolution anomalies
        const screen = window.screen;
        if (screen.width % 2 !== 0 || screen.height % 2 !== 0) {
            this.logViolation('Suspicious screen resolution detected');
        }

        // Check for VM-specific features
        if (navigator.webdriver || navigator.webdriver === true) {
            this.logViolation('WebDriver detected (possible automation)');
        }
    }

    // Face detection (using face-api.js)
    setupFaceDetection() {
        if (typeof faceapi !== 'undefined') {
            this.initFaceDetection();
        }
    }

    async initFaceDetection() {
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
            await faceapi.nets.faceRecognitionNet.loadFromUri('/models');

            const video = document.createElement('video');
            video.id = 'faceDetectionVideo';
            video.style.display = 'none';
            document.body.appendChild(video);

            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.play();

            this.faceDetectionInterval = setInterval(async () => {
                const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
                
                if (detections.length === 0) {
                    this.logViolation('No face detected');
                } else if (detections.length > 1) {
                    this.logViolation('Multiple faces detected');
                }
            }, 5000);
        } catch (error) {
            console.log('Face detection not available:', error);
        }
    }

    // Auto screenshot capture
    setupAutoScreenshot() {
        this.screenshotInterval = setInterval(() => {
            this.captureScreenshot();
        }, 30000); // Every 30 seconds
    }

    async captureScreenshot() {
        try {
            const canvas = await html2canvas(document.body, {
                ignoreElements: (element) => {
                    return element.id === 'faceDetectionVideo';
                }
            });
            
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Send to server
            fetch('../admin/save_screenshot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    image: imageData,
                    timestamp: Date.now(),
                    sesi_id: window.sesiId
                })
            });
        } catch (error) {
            console.error('Screenshot capture failed:', error);
        }
    }

    logViolation(type) {
        this.violationCount++;
        
        // Send to server
        fetch('../admin/log_violation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: type,
                timestamp: Date.now(),
                sesi_id: window.sesiId,
                violation_count: this.violationCount
            })
        });

        if (this.violationCount >= this.maxViolations) {
            this.triggerForceSubmit();
        }
    }

    showWarning(message) {
        // Create custom alert
        const alertDiv = document.createElement('div');
        alertDiv.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 10px;
            z-index: 10000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 400px;
            text-align: center;
        `;
        alertDiv.innerHTML = `
            <h4><i class="fas fa-exclamation-triangle"></i> Peringatan!</h4>
            <p>${message}</p>
            <p>Pelanggaran: ${this.violationCount}/${this.maxViolations}</p>
            <button onclick="this.parentElement.remove()" class="btn btn-light btn-sm">OK</button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    }

    triggerForceSubmit() {
        alert('Terlalu banyak pelanggaran! Ujian akan disubmit otomatis.');
        window.location.href = `submit_ujian.php?sesi_id=${window.sesiId}&force_submit=true`;
    }

    destroy() {
        if (this.screenshotInterval) {
            clearInterval(this.screenshotInterval);
        }
        if (this.faceDetectionInterval) {
            clearInterval(this.faceDetectionInterval);
        }
        if (this.checkScreenRecordingInterval) {
            clearInterval(this.checkScreenRecordingInterval);
        }
    }
}

// Initialize anti-cheat system
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('ujian.php')) {
        window.antiCheatSystem = new AntiCheatSystem();
    }
});