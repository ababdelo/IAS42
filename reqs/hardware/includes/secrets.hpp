#ifndef SECRETS_HPP
#define SECRETS_HPP

#define WIFI_SSID "Wokwi-GUEST"
#define WIFI_PASSWORD ""
#define MQTT_SERVER "broker.hivemq.com"
#define MQTT_PORT 1883

// Physical Identity matching the Database
#define NODE_ID "A84F92"
#define MAC_ADDRESS "A8:4F:92:00:42:00"
#define NODE_SECRET "c72f9a83d90c72f9a83d90c72f9a83d90"

// Secure Routing Topics
#define TOPIC_TELEMETRY "ias42/v1/nodes/" NODE_ID "/telemetry"
#define TOPIC_CONTROL "ias42/v1/nodes/" NODE_ID "/control"

#endif
