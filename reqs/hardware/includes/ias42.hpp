#ifndef IAS42_HPP
#define IAS42_HPP

#include <Arduino.h>
#include <WiFi.h>
#include <PubSubClient.h>
#include <DHT.h>
#include <ArduinoJson.h>
#include "config.hpp"
#include "secrets.hpp"

class IAS42 {
public:
    IAS42();
    void begin();
    void update();

    static void globalMqttCallback(char* topic, byte* payload, unsigned int length);
    void processMqttMessage(char* topic, byte* payload, unsigned int length);

private:
    void setupWiFi();
    void reconnect();
    void checkSensorsAndPublish();
    long readTankDistance();
    String generateHMAC(String payload, String secret);

    WiFiClient espClient;
    PubSubClient client;
    DHT dht;

    bool pumpState;
    unsigned long pumpStartTime;
    unsigned long lastReadTime;
    unsigned long lastHeartbeat;

    // Previous Sensor Values
    float lastTemp;
    float lastHum;
    int lastMoist;
    float lastPH;
    int lastN;
    int lastP;
    int lastK;
    float lastWindSpeed;
    int lastBright;
    bool lastRain;
    long lastDist;
    bool lastPump;
};

#endif
