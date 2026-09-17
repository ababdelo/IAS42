#ifndef CONFIG_HPP
#define CONFIG_HPP

// --- Pin Definitions ---
#define DHTPIN 15
#define DHTTYPE DHT22

// Wi-Fi Safe Analog Pins (ADC1)
#define PIN_SOIL_MOISTURE 34
#define PIN_SOIL_PH 35
#define PIN_WIND_SPEED 33
#define PIN_SOIL_N 36  // VP
#define PIN_SOIL_P 39  // VN
#define PIN_SOIL_K 32

// Digital Pins
#define PIN_LDR 25     // Moved to Digital (using sensor DO pin)
#define PIN_TRIG 5
#define PIN_ECHO 18
#define PIN_RAIN 13
#define PIN_RELAY_PUMP 19

#endif
