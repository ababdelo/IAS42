# IAS42: Intelligent Agriculture System

IAS42 is a smart agriculture project that combines an ESP32-based hardware system with a web application for monitoring and managing agricultural data.

The project includes sensor acquisition, MQTT communication, a PHP/MySQL web application, user authentication, and a Wokwi simulation environment.

## Features

- ESP32-based agricultural monitoring
- Temperature and humidity monitoring
- Soil moisture and soil pH monitoring
- Wind, light, rain, and water tank monitoring
- Water pump control through a relay
- MQTT communication between the ESP32 and broker
- Web dashboard
- User registration and login
- Account verification with OTP
- Password recovery and reset
- Google authentication
- Email notifications
- Multilingual interface
- Responsive web interface
- Docker-based development environment
- Wokwi hardware simulation

## Technologies

### Hardware

- ESP32
- DHT22
- Soil moisture sensor
- Soil pH sensor
- Wind speed sensor
- LDR
- Rain sensor
- Ultrasonic sensor
- Relay module

### Software

- PHP 8.4
- MySQL 8.4
- Apache
- Docker & Docker Compose
- JavaScript
- CSS
- PDO
- MQTT
- PlatformIO
- Wokwi

## Project Structure

```
IAS42/
├── reqs/
│   ├── env/
│   ├── hardware/
│   └── software/
│       ├── database/
│       ├── server/
│       └── website/
├── makefile
├── license
└── readme.md
```

## Setup

### 1. Configure the environment

Copy the example environment file:

```bash
cp reqs/env/.env.example reqs/env/.env
```

Then update it with the required configuration.

### 2. Start the web application

Build and start the software stack:

```bash
make build sw
```

The application runs through Docker using Apache, PHP, and MySQL.

### 3. Build the ESP32 firmware

```bash
make build hw
```

For a connected ESP32:

```bash
make upload
```

To open the serial monitor:

```bash
make monitor
```

## MQTT

The ESP32 publishes sensor telemetry through MQTT and listens for pump control commands.

The firmware uses the following topics:

```text
ed42/ias/parcelle1/telemetry
ed42/ias/parcelle1/control
```

## Simulation

The hardware part includes a Wokwi simulation located in:

```text
reqs/hardware/
```

It can be used to test the ESP32 firmware and connected components without physical hardware.

## Current State

IAS42 is currently a work in progress.

The authentication system, web interface, database setup, ESP32 firmware, MQTT communication, and hardware simulation are implemented.

Some application sections such as analytics, history, notifications, profile/settings management, and the complete connection between live hardware telemetry and the web dashboard are still under development.

## License

This project is licensed under the MIT License.
