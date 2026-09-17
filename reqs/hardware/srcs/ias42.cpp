#include "ias42.hpp"
#include "mbedtls/md.h" // Native ESP32 crypto library

IAS42 *instance = nullptr;

IAS42::IAS42() : dht(DHTPIN, DHTTYPE),
                 client(espClient),
                 pumpState(false), pumpStartTime(0), lastReadTime(0), lastHeartbeat(0),
                 lastTemp(-99.0), lastHum(-99.0), lastMoist(-99), lastPH(-99.0),
                 lastN(-99), lastP(-99), lastK(-99),
                 lastWindSpeed(-99.0), lastBright(-99), lastRain(false), lastDist(-99), lastPump(false)
{
    instance = this;
}

void IAS42::begin()
{
    Serial.begin(115200);

    pinMode(PIN_TRIG, OUTPUT);
    pinMode(PIN_ECHO, INPUT);
    pinMode(PIN_RAIN, INPUT_PULLUP);
    pinMode(PIN_LDR, INPUT); // Digital LDR Input
    pinMode(PIN_RELAY_PUMP, OUTPUT);
    digitalWrite(PIN_RELAY_PUMP, LOW);

    dht.begin();
    setupWiFi();

    client.setServer(MQTT_SERVER, MQTT_PORT);
    client.setCallback(globalMqttCallback);
    client.setBufferSize(1024);

    Serial.println("System Ready! Secure Event-Driven Telemetry Active.");
}

void IAS42::setupWiFi()
{
    Serial.print("Connecting to Wi-Fi...");
    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    while (WiFi.status() != WL_CONNECTED)
    {
        delay(500);
        Serial.print(".");
    }
    Serial.println("\nWi-Fi Connected!");
}

void IAS42::reconnect()
{
    while (!client.connected())
    {
        Serial.print("Connecting to HiveMQ MQTT Broker...");
        if (client.connect(NODE_ID))
        {
            Serial.println("Connected!");
            client.subscribe(TOPIC_CONTROL);
        }
        else
        {
            Serial.print("Failed, retrying in 5 seconds...");
            delay(5000);
        }
    }
}

void IAS42::globalMqttCallback(char *topic, byte *payload, unsigned int length)
{
    if (instance != nullptr)
    {
        instance->processMqttMessage(topic, payload, length);
    }
}

void IAS42::processMqttMessage(char *topic, byte *payload, unsigned int length)
{
    String command = "";
    for (int i = 0; i < length; i++)
    {
        command += (char)payload[i];
    }

    if (command == "PUMP_ON" && !pumpState)
    {
        pumpState = true;
        digitalWrite(PIN_RELAY_PUMP, HIGH);
        pumpStartTime = millis();
        Serial.println(">>> WEB COMMAND: Pump Turned ON for 5 seconds! <<<");
    }
}

long IAS42::readTankDistance()
{
    digitalWrite(PIN_TRIG, LOW);
    delayMicroseconds(2);
    digitalWrite(PIN_TRIG, HIGH);
    delayMicroseconds(10);
    digitalWrite(PIN_TRIG, LOW);

    long duration = pulseIn(PIN_ECHO, HIGH, 30000);
    return duration * 0.034 / 2;
}

// Secure HMAC-SHA256 Generator
String IAS42::generateHMAC(String payload, String secret)
{
    mbedtls_md_context_t ctx;
    mbedtls_md_type_t md_type = MBEDTLS_MD_SHA256;

    const size_t payloadLength = payload.length();
    const size_t secretLength = secret.length();
    unsigned char hmacResult[32];

    mbedtls_md_init(&ctx);
    mbedtls_md_setup(&ctx, mbedtls_md_info_from_type(md_type), 1);
    mbedtls_md_hmac_starts(&ctx, (const unsigned char *)secret.c_str(), secretLength);
    mbedtls_md_hmac_update(&ctx, (const unsigned char *)payload.c_str(), payloadLength);
    mbedtls_md_hmac_finish(&ctx, hmacResult);
    mbedtls_md_free(&ctx);

    String hash = "";
    for (int i = 0; i < 32; i++)
    {
        char str[3];
        sprintf(str, "%02x", (int)hmacResult[i]);
        hash += str;
    }
    return hash;
}

void IAS42::update()
{
    if (!client.connected())
    {
        reconnect();
    }
    client.loop();

    if (pumpState && (millis() - pumpStartTime > 5000))
    {
        pumpState = false;
        digitalWrite(PIN_RELAY_PUMP, LOW);
        Serial.println(">>> Pump cycle finished. Turned OFF. <<<");
    }

    if (millis() - lastReadTime > 150)
    {
        lastReadTime = millis();
        checkSensorsAndPublish();
    }
}

void IAS42::checkSensorsAndPublish()
{
    float temp = dht.readTemperature();
    float hum = dht.readHumidity();
    if (isnan(temp) || isnan(hum))
    {
        temp = 0.0;
        hum = 0.0;
    }

    int soilMoisture = map(analogRead(PIN_SOIL_MOISTURE), 0, 4095, 0, 100);
    float soilPH = map(analogRead(PIN_SOIL_PH), 0, 4095, 0, 140) / 10.0;
    float windSpeed = map(analogRead(PIN_WIND_SPEED), 0, 4095, 0, 500) / 10.0;

    // NPK Readings
    int valN = map(analogRead(PIN_SOIL_N), 0, 4095, 0, 200);
    int valP = map(analogRead(PIN_SOIL_P), 0, 4095, 0, 200);
    int valK = map(analogRead(PIN_SOIL_K), 0, 4095, 0, 200);

    // Digital Brightness (LOW on Wokwi LDR DO means light is present)
    int brightness = (digitalRead(PIN_LDR) == LOW) ? 100 : 0;

    bool isRaining = (digitalRead(PIN_RAIN) == LOW);
    long distCm = readTankDistance();

    bool changed = false;
    if (abs(temp - lastTemp) >= 0.2)
        changed = true;
    if (abs(hum - lastHum) >= 0.5)
        changed = true;
    if (abs(soilMoisture - lastMoist) >= 1)
        changed = true;
    if (abs(soilPH - lastPH) >= 0.1)
        changed = true;
    if (abs(valN - lastN) >= 2)
        changed = true;
    if (abs(valP - lastP) >= 2)
        changed = true;
    if (abs(valK - lastK) >= 2)
        changed = true;
    if (abs(windSpeed - lastWindSpeed) >= 1.0)
        changed = true;
    if (abs(brightness - lastBright) >= 2)
        changed = true;
    if (isRaining != lastRain)
        changed = true;
    if (abs(distCm - lastDist) >= 2)
        changed = true;
    if (pumpState != lastPump)
        changed = true;

    bool heartbeat = (millis() - lastHeartbeat > 10000);

    if (changed || heartbeat)
    {
        lastTemp = temp;
        lastHum = hum;
        lastMoist = soilMoisture;
        lastPH = soilPH;
        lastN = valN;
        lastP = valP;
        lastK = valK;
        lastWindSpeed = windSpeed;
        lastBright = brightness;
        lastRain = isRaining;
        lastDist = distCm;
        lastPump = pumpState;
        lastHeartbeat = millis();

        // 1. Build Inner Payload
        JsonDocument innerDoc;
        innerDoc["temperature"] = round(temp * 10) / 10.0;
        innerDoc["humidity"] = round(hum * 10) / 10.0;
        innerDoc["soil_moisture"] = soilMoisture;
        innerDoc["ph"] = round(soilPH * 10) / 10.0;
        innerDoc["nitrogen"] = valN;
        innerDoc["phosphorus"] = valP;
        innerDoc["potassium"] = valK;
        innerDoc["rain"] = isRaining;
        innerDoc["wind_speed"] = round(windSpeed * 10) / 10.0;
        innerDoc["brightness"] = brightness;
        innerDoc["water_level"] = distCm;
        innerDoc["pump_status"] = pumpState;

        String innerPayload;
        serializeJson(innerDoc, innerPayload);

        // 2. Hash it
        String signature = generateHMAC(innerPayload, NODE_SECRET);

        // 3. Build Outer Envelope
        JsonDocument outerDoc;
        outerDoc["node_id"] = NODE_ID;
        outerDoc["payload"] = innerPayload;
        outerDoc["signature"] = signature;

        char jsonBuffer[768];
        serializeJson(outerDoc, jsonBuffer);

        Serial.print(changed ? "[INSTANT CHANGE] -> " : "[HEARTBEAT] -> ");
        Serial.println(jsonBuffer);

        const bool published = client.publish(TOPIC_TELEMETRY, jsonBuffer);
        if (!published)
        {
            Serial.println("[MQTT ERROR] Telemetry publish failed. Check MQTT connection or packet size.");
        }
        else
        {
            Serial.println("[MQTT OK] Telemetry published.");
        }
    }
}
