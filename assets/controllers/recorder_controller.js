import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'preview',
        'startBtn',
        'pauseBtn',
        'stopBtn',
        'timerContainer',
        'timer',
        'videoTitle',
        'videoDescription',
        'audioCheckbox',
        'codecSelect'
    ];

    mediaRecorder = null;
    recordedChunks = [];
    stream = null;
    timerInterval = null;
    recordingTime = 0;
    uploadSessionId = null;
    chunkNumber = 0;
    chunkUploadInterval = 5000; // Upload chunks every 5 seconds

    async startRecording() {
        try {
            const displayMediaOptions = {
                video: { cursor: 'always' },
                audio: this.audioCheckboxTarget.checked
            };

            this.stream = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions);

            // Generate unique upload session ID
            this.uploadSessionId = this.generateUUID();
            this.chunkNumber = 0;
            this.shouldMarkAsLast = false;

            // Update preview
            const video = document.createElement('video');
            video.srcObject = this.stream;
            video.play();
            video.style.width = '100%';
            video.style.height = '100%';
            this.previewTarget.innerHTML = '';
            this.previewTarget.appendChild(video);

            // Set up MediaRecorder
            const codec = this.codecSelectTarget.value === 'vp9'
                ? 'video/webm;codecs=vp9'
                : 'video/webm;codecs=vp8';

            const options = {
                mimeType: codec,
                videoBitsPerSecond: 2500000
            };

            this.mediaRecorder = new MediaRecorder(this.stream, options);
            this.recordedChunks = [];

            this.mediaRecorder.ondataavailable = async (event) => {
                if (event.data.size > 0) {
                    // Check if this should be marked as the last chunk
                    const isLast = this.shouldMarkAsLast || false;
                    await this.uploadChunk(event.data, isLast);

                    // Reset flag after upload
                    if (isLast) {
                        this.shouldMarkAsLast = false;
                    }
                }
            };

            this.mediaRecorder.onstop = async () => {
                // Mark that we want the next chunk to be the last
                this.shouldMarkAsLast = true;

                // Request any final data from the recorder
                if (this.mediaRecorder.state !== 'inactive') {
                    try {
                        this.mediaRecorder.requestData();
                    } catch (e) {
                        console.log('Could not request final data:', e);
                    }
                }

                // Wait a bit for final chunk to upload, then if no chunk came, send empty one
                setTimeout(async () => {
                    if (this.shouldMarkAsLast) {
                        // No final chunk received, send an empty one to trigger processing
                        console.log('Sending final empty chunk to trigger processing');
                        const emptyBlob = new Blob([], { type: 'video/webm' });
                        await this.uploadChunk(emptyBlob, true);
                        this.shouldMarkAsLast = false;
                    }
                    this.handleRecordingStop();
                }, 200);
            };

            // Handle stream stop (user clicks stop in browser prompt)
            this.stream.getTracks().forEach(track => {
                track.onended = () => {
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.stopRecording();
                    }
                };
            });

            // Start recording with timeslice to get chunks periodically
            this.mediaRecorder.start(this.chunkUploadInterval);

            // Update UI
            this.startBtnTarget.disabled = true;
            this.startBtnTarget.hidden = true;
            this.pauseBtnTarget.disabled = false;
            this.pauseBtnTarget.hidden = false;
            this.stopBtnTarget.disabled = false;
            this.stopBtnTarget.hidden = false;

            // Start timer
            this.recordingTime = 0;
            this.timerContainerTarget.hidden = false;
            this.timerInterval = setInterval(() => {
                this.recordingTime++;
                this.updateTimer();
            }, 1000);

            // Pre-fill title with timestamp
            this.videoTitleTarget.value = `Screen Recording - ${new Date().toLocaleString()}`;

        } catch (err) {
            if (err.name !== 'NotAllowedError') {
                console.error('Error: ', err);
                alert('Failed to start recording: ' + err.message);
            }
        }
    }

    pauseRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
            this.mediaRecorder.pause();
            this.pauseBtnTarget.textContent = '⏯️ Resume';
        } else if (this.mediaRecorder && this.mediaRecorder.state === 'paused') {
            this.mediaRecorder.resume();
            this.pauseBtnTarget.textContent = '⏸️ Pause';
        }
    }

    stopRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
            clearInterval(this.timerInterval);

            // Stop all tracks
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }

            // Update UI
            this.startBtnTarget.disabled = false;
            this.startBtnTarget.hidden = false;
            this.pauseBtnTarget.disabled = true;
            this.pauseBtnTarget.hidden = true;
            this.pauseBtnTarget.textContent = '⏸️ Pause';
            this.stopBtnTarget.disabled = true;
            this.stopBtnTarget.hidden = true;
            this.timerContainerTarget.hidden = true;
        }
    }

    updateTimer() {
        const hours = Math.floor(this.recordingTime / 3600);
        const minutes = Math.floor((this.recordingTime % 3600) / 60);
        const seconds = this.recordingTime % 60;

        this.timerTarget.textContent = [hours, minutes, seconds]
            .map(v => String(v).padStart(2, '0'))
            .join(':');
    }

    async uploadChunk(chunkBlob, isLast) {
        const title = this.videoTitleTarget.value || 'Untitled Recording';
        const description = this.videoDescriptionTarget.value || '';

        const formData = new FormData();
        formData.append('chunk', chunkBlob, `chunk_${this.chunkNumber}.webm`);
        formData.append('uploadSessionId', this.uploadSessionId);
        formData.append('chunkNumber', this.chunkNumber);
        formData.append('isLast', isLast ? 'true' : 'false');
        formData.append('title', title);
        formData.append('description', description);

        try {
            const response = await fetch('/api/upload-chunk', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                console.log(`Chunk ${this.chunkNumber} uploaded successfully`);
                this.chunkNumber++;
            } else {
                console.error('Chunk upload failed:', data.error);
            }
        } catch (error) {
            console.error('Chunk upload error:', error);
        }
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    handleRecordingStop() {
        // Show success message - video is being processed in background
        alert('Recording stopped! Your video is being processed in the background. You will find it in the gallery shortly.');

        // Redirect to gallery
        setTimeout(() => window.location.href = '/gallery', 1500);
    }
}

