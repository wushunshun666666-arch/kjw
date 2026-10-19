/**
 * 原生 WebSocket 连接管理模块。
 *
 * 统一处理消息解析、心跳、指数退避重连和页面销毁，业务模块只处理领域事件。
 */
define(function (require, exports) {
    'use strict';

    /** 创建并启动一个可自动恢复的 WebSocket 连接。 */
    exports.connect = function (config) {
        var socket = null;
        var reconnectTimer = null;
        var heartbeatTimer = null;
        var reconnectAttempts = 0;
        var stopped = false;

        /** 将 HTTP(S) 消息中心地址转换为 WebSocket 地址并附加短期 Token。 */
        function connectionUrl() {
            return config.server.replace(/^http/, 'ws').replace(/\/$/, '')
                + '?token=' + encodeURIComponent(config.token);
        }

        /** 通知业务模块当前连接状态。 */
        function notifyStatus(status) {
            if (typeof config.onStatus === 'function') {
                config.onStatus(status);
            }
        }

        /** 输出不包含连接地址和Token的实时连接诊断信息。 */
        function logConnection(level, message) {
            if (!window.console || typeof window.console[level] !== 'function') {
                return;
            }
            window.console[level]('[WebSocket] ' + message);
        }

        /** 停止当前心跳。 */
        function stopHeartbeat() {
            if (heartbeatTimer !== null) {
                window.clearInterval(heartbeatTimer);
                heartbeatTimer = null;
            }
        }

        /** 启动应用层心跳，帮助代理和网关保持空闲连接。 */
        function startHeartbeat() {
            stopHeartbeat();
            heartbeatTimer = window.setInterval(function () {
                if (socket && socket.readyState === window.WebSocket.OPEN) {
                    socket.send(JSON.stringify({type: 'ping'}));
                }
            }, 25000);
        }

        /** 按指数退避安排下一次重连。 */
        function scheduleReconnect() {
            if (stopped || reconnectTimer !== null) {
                return;
            }
            reconnectAttempts += 1;
            var delay = Math.min(30000, 1000 * Math.pow(2, Math.min(reconnectAttempts - 1, 5)));
            notifyStatus('reconnecting');
            reconnectTimer = window.setTimeout(function () {
                reconnectTimer = null;
                open();
            }, delay);
        }

        /** 解析并转交一条服务端业务消息。 */
        function handleMessage(event) {
            var payload;
            try {
                payload = JSON.parse(event.data);
            } catch (error) {
                return;
            }
            if (payload.data) {
                payload = payload.data;
            }
            if (payload.type === 'kefu.message' && payload.messageId && socket.readyState === window.WebSocket.OPEN) {
                socket.send(JSON.stringify({type: 'delivery_ack', messageId: Number(payload.messageId)}));
            }
            if (typeof config.onMessage === 'function') {
                config.onMessage(payload);
            }
        }

        /** 建立一次 WebSocket 连接。 */
        function open() {
            if (stopped || !config.server || !config.token) {
                notifyStatus('disabled');
                return;
            }
            notifyStatus('connecting');
            socket = new window.WebSocket(connectionUrl());
            socket.onopen = function () {
                reconnectAttempts = 0;
                logConnection('log', 'hello welcome，连接成功。');
                notifyStatus('connected');
                startHeartbeat();
            };
            socket.onmessage = handleMessage;
            socket.onerror = function () {
                logConnection('error', '连接失败。');
                notifyStatus('error');
            };
            socket.onclose = function (event) {
                stopHeartbeat();
                if (!stopped) {
                    logConnection('warn', '连接已关闭，code=' + Number(event.code || 0) + '。');
                }
                notifyStatus('disconnected');
                scheduleReconnect();
            };
        }

        /** 主动停止连接及所有重连计时器。 */
        function close() {
            stopped = true;
            stopHeartbeat();
            if (reconnectTimer !== null) {
                window.clearTimeout(reconnectTimer);
                reconnectTimer = null;
            }
            if (socket) {
                socket.onclose = null;
                socket.close();
                socket = null;
            }
            notifyStatus('closed');
        }

        open();
        return {close: close};
    };
});
