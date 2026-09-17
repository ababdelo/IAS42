#include "ias42.hpp"

IAS42 myGarden;

void setup() {
    myGarden.begin();
}

void loop() {
    myGarden.update();
}
