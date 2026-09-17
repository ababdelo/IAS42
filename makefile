# =============================================================================== #
#                          Author: Abderrahmane Abdelouafi                        #
#                    Creation Date: August 08, 2026 13:45 AM                      #
#                    Last Updated: August 14, 2026                                #
#                              File Name: makefile                                #
#                            --- Code Description ---                             #
#                Simplified Docker management Makefile for IAS42.                 #
#              Includes Self-Signed SSL generation for development.               #
# =============================================================================== #

# ================================================= #
#            1. CONFIGURATION & VARIABLES           #
# ================================================= #

PROJECT_NAME        := IAS42
SOFTWARE_DIR        := reqs/software
HARDWARE_DIR        := reqs/hardware
COMPOSE_FILE        := $(SOFTWARE_DIR)/docker-compose.yml
ENV_FILE            := reqs/env/.env

SELECTED_TARGET     := $(if $(filter hw sw both,$(word 2,$(MAKECMDGOALS))),$(word 2,$(MAKECMDGOALS)),sw)
REBUILD_TARGET      := $(if $(filter hw sw both,$(word 2,$(MAKECMDGOALS))),$(word 2,$(MAKECMDGOALS)),)

# SSL Paths
SSL_DIR             := $(SOFTWARE_DIR)/server/ssl
SSL_CERTS_DIR       := $(SSL_DIR)/certs
SSL_PRIVATE_DIR     := $(SSL_DIR)/private

CERT_FILE           := $(SSL_CERTS_DIR)/server.crt
CA_BUNDLE_FILE      := $(SSL_CERTS_DIR)/ca_bundle.crt
FULLCHAIN_FILE      := $(SSL_CERTS_DIR)/fullchain.pem
CSR_FILE            := $(SSL_CERTS_DIR)/server.csr
KEY_FILE            := $(SSL_PRIVATE_DIR)/server.key

# Colors for terminal output
RESET       = \033[0m
WHITE       = \033[1;37m
GREY        = \033[1;90m
ORANGE      = \033[1;38;5;208m
YELLOW      = \033[1;33m
RED         = \033[1;31m
BLUE        = \033[1;34m
CYAN        = \033[1;36m
GREEN       = \033[1;32m
MAGENTA     = \033[1;35m

# Command Hiding
export VERBOSE = TRUE

ifeq ($(VERBOSE),FALSE)
    HIDE =
else
    HIDE = @
endif

# Base Commands
DC  = docker compose --env-file $(ENV_FILE) -f $(COMPOSE_FILE)
PIO = $(HIDE) pio run -d $(HARDWARE_DIR)

.DEFAULT_GOAL := help

# ================================================= #
#             2. ROUTING & GLOBAL COMMANDS          #
# ================================================= #
sw:
	@:

hw:
	@:

both:
	@:

build:
	@if [ "$(SELECTED_TARGET)" = "hw" ]; then \
		$(MAKE) hw-build; \
	elif [ "$(SELECTED_TARGET)" = "both" ]; then \
		$(MAKE) both-build; \
	elif [ "$(SELECTED_TARGET)" = "sw" ] && [ "$(filter hw sw both,$(word 2,$(MAKECMDGOALS)))" != "" ]; then \
		$(MAKE) sw-build; \
	else \
		printf "${CYAN}Choose a target:${RESET}\n"; \
		printf "${GREEN}1) Software${RESET}\n"; \
		printf "${YELLOW}2) Hardware${RESET}\n"; \
		printf "${CYAN}3) Both${RESET}\n"; \
		read -p "Enter your choice (1-3): " choice; \
		case $$choice in \
			1) printf "${GREEN}Building Software...${RESET}\n"; \
			   $(MAKE) sw-build ;; \
			2) printf "${YELLOW}Building Hardware...${RESET}\n"; \
			   $(MAKE) hw-build ;; \
			3) printf "${CYAN}Building Software and Hardware...${RESET}\n"; \
			   $(MAKE) both-build ;; \
			*) printf "${RED}Invalid choice. Please select a valid option.${RESET}\n"; \
			   exit 1 ;; \
		esac; \
	fi

clean:
	@if [ "$(SELECTED_TARGET)" = "hw" ]; then \
		$(MAKE) hw-clean; \
	elif [ "$(SELECTED_TARGET)" = "both" ]; then \
		$(MAKE) both-clean; \
	elif [ "$(SELECTED_TARGET)" = "sw" ] && [ "$(filter hw sw both,$(word 2,$(MAKECMDGOALS)))" != "" ]; then \
		$(MAKE) sw-clean; \
	else \
		printf "${CYAN}Choose a target to clean:${RESET}\n"; \
		printf "${GREEN}1) Software${RESET}\n"; \
		printf "${YELLOW}2) Hardware${RESET}\n"; \
		printf "${CYAN}3) Both${RESET}\n"; \
		read -p "Enter your choice (1-3): " choice; \
		case $$choice in \
			1) printf "${GREEN}Cleaning Software...${RESET}\n"; \
			   $(MAKE) sw-clean ;; \
			2) printf "${YELLOW}Cleaning Hardware...${RESET}\n"; \
			   $(MAKE) hw-clean ;; \
			3) printf "${CYAN}Cleaning Software and Hardware...${RESET}\n"; \
			   $(MAKE) both-clean ;; \
			*) printf "${RED}Invalid choice. Please select a valid option.${RESET}\n"; \
			   exit 1 ;; \
		esac; \
	fi

up:
	$(HIDE)printf "$(GREEN)Starting $(PROJECT_NAME) containers...\n$(RESET)"
	$(HIDE)$(DC) up -d

down:
	$(HIDE)printf "$(YELLOW)Stopping and removing $(PROJECT_NAME) containers...\n$(RESET)"
	$(HIDE)$(DC) down

reboot:
	$(HIDE)printf "$(ORANGE)Restarting $(PROJECT_NAME) containers...\n$(RESET)"
	$(HIDE)$(DC) restart

rebuild:
	@target="$(REBUILD_TARGET)"; \
	if [ -z "$$target" ]; then \
		printf "${CYAN}Choose a target to rebuild:${RESET}\n"; \
		printf "${GREEN}1) Software${RESET}\n"; \
		printf "${YELLOW}2) Hardware${RESET}\n"; \
		printf "${CYAN}3) Both${RESET}\n"; \
		read -p "Enter your choice (1-3): " choice; \
		case $$choice in \
			1) target=sw ;; \
			2) target=hw ;; \
			3) target=both ;; \
			*) printf "${RED}Invalid choice. Please select 1, 2, or 3.${RESET}\n"; \
			   exit 1 ;; \
		esac; \
	fi; \
	case $$target in \
		sw) printf "${GREEN}Rebuilding Software...${RESET}\n"; \
		    $(MAKE) sw-clean sw-build ;; \
		hw) printf "${YELLOW}Rebuilding Hardware...${RESET}\n"; \
		    $(MAKE) hw-clean hw-build ;; \
		both) printf "${CYAN}Rebuilding Software and Hardware...${RESET}\n"; \
		      $(MAKE) both-rebuild ;; \
		*) printf "${RED}Invalid target. Please select sw, hw, or both.${RESET}\n"; \
		   exit 1 ;; \
	esac

# ================================================= #
#               3. SOFTWARE / DOCKER                #
# ================================================= #
sw-build: ssl
	$(HIDE)printf "$(BLUE)Building and starting $(PROJECT_NAME) containers...\n$(RESET)"
	$(HIDE)$(DC) up -d --build

sw-clean: down
	$(HIDE)printf "$(RED)Cleaning software containers, images, database volume, and runtime logs...\n$(RESET)"
	$(HIDE)$(DC) down -v --rmi all --remove-orphans
	$(HIDE)printf "$(RED)Cleaning server logs...\n$(RESET)"
	$(HIDE)rm -rf \
		$(SOFTWARE_DIR)/server/logs/apache/* \
		$(SOFTWARE_DIR)/server/logs/application/* 2>/dev/null || true
	$(HIDE)printf "$(GREEN)Software cleanup completed.\n$(RESET)"
	$(HIDE)printf "$(GREEN)SSL files were preserved.\n$(RESET)"

both-build:
	$(MAKE) sw-build
	$(MAKE) hw-build

both-clean:
	$(MAKE) sw-clean
	$(MAKE) hw-clean

both-rebuild:
	$(MAKE) both-clean
	$(MAKE) both-build

ssl:
	@printf "${CYAN}Validating IAS42 SSL deployment files...${RESET}\n"; \
	if [ ! -s "$(CERT_FILE)" ]; then \
		printf "${RED}ERROR: Missing server certificate:${RESET}\n"; \
		printf "  $(CERT_FILE)\n"; \
		exit 1; \
	fi; \
	if [ ! -s "$(KEY_FILE)" ]; then \
		printf "${RED}ERROR: Missing private key:${RESET}\n"; \
		printf "  $(KEY_FILE)\n"; \
		exit 1; \
	fi; \
	if [ ! -s "$(CA_BUNDLE_FILE)" ]; then \
		printf "${RED}ERROR: Missing CA bundle:${RESET}\n"; \
		printf "  $(CA_BUNDLE_FILE)\n"; \
		exit 1; \
	fi; \
	if [ ! -s "$(FULLCHAIN_FILE)" ]; then \
		printf "${RED}ERROR: Missing fullchain:${RESET}\n"; \
		printf "  $(FULLCHAIN_FILE)\n"; \
		exit 1; \
	fi; \
	printf "${GREEN}SSL files found.${RESET}\n"; \
	printf "\n${WHITE}Certificate:${RESET}\n"; \
	openssl x509 \
		-in "$(CERT_FILE)" \
		-noout \
		-subject \
		-issuer \
		-dates \
		-ext subjectAltName; \
	printf "\n${WHITE}Checking certificate expiration...${RESET}\n"; \
	openssl x509 \
		-in "$(CERT_FILE)" \
		-checkend 0 \
		-noout; \
	printf "${GREEN}Certificate is currently valid.${RESET}\n"; \
	printf "\n${WHITE}Checking certificate/private-key match...${RESET}\n"; \
	CERT_MODULUS_HASH=$$(openssl x509 \
		-in "$(CERT_FILE)" \
		-noout \
		-modulus | \
		openssl sha256 | \
		cut -d ' ' -f 2); \
	KEY_MODULUS_HASH=$$(openssl rsa \
		-in "$(KEY_FILE)" \
		-noout \
		-modulus | \
		openssl sha256 | \
		cut -d ' ' -f 2); \
	printf "Certificate modulus: %s\n" "$$CERT_MODULUS_HASH"; \
	printf "Private key modulus: %s\n" "$$KEY_MODULUS_HASH"; \
	if [ -z "$$CERT_MODULUS_HASH" ] || [ -z "$$KEY_MODULUS_HASH" ]; then \
		printf "${RED}ERROR: Could not determine certificate/private-key modulus.${RESET}\n"; \
		exit 1; \
	fi; \
	if [ "$$CERT_MODULUS_HASH" != "$$KEY_MODULUS_HASH" ]; then \
		printf "${RED}ERROR: Certificate does not match private key.${RESET}\n"; \
		exit 1; \
	fi; \
	printf "${GREEN}Certificate matches private key.${RESET}\n"; \
	printf "\n${GREEN}SSL validation completed successfully.${RESET}\n"

logs:
	@printf "${CYAN}Choose a service to view logs:${RESET}\n"; \
	printf "${GREEN}1) Apache${RESET}\n"; \
	printf "${YELLOW}2) MySQL${RESET}\n"; \
	printf "${MAGENTA}3) MQTT Bridge${RESET}\n"; \
	read -p "Enter your choice (1-3): " choice; \
	case $$choice in \
		1) printf "${GREEN}Viewing logs for Apache...${RESET}\n"; \
		   $(DC) logs -f apache ;; \
		2) printf "${YELLOW}Viewing logs for MySQL...${RESET}\n"; \
		   $(DC) logs -f mysql ;; \
		3) printf "${MAGENTA}Viewing logs for MQTT Bridge...${RESET}\n"; \
		   $(DC) logs -f mqtt-bridge ;; \
		*) printf "${RED}Invalid choice. Please select 1, 2, or 3.${RESET}\n"; \
		   exit 1 ;; \
	esac

shell:
	@printf "${CYAN}Choose a service to open a shell:${RESET}\n"; \
	printf "${GREEN}1) Apache${RESET}\n"; \
	printf "${YELLOW}2) MySQL${RESET}\n"; \
	printf "${MAGENTA}3) MQTT Bridge${RESET}\n"; \
	read -p "Enter your choice (1-3): " choice; \
	case $$choice in \
		1) printf "${GREEN}Opening shell for Apache...${RESET}\n"; \
		   $(DC) exec apache bash ;; \
		2) printf "${YELLOW}Opening shell for MySQL...${RESET}\n"; \
		   $(DC) exec mysql bash ;; \
		3) printf "${MAGENTA}Opening shell for MQTT Bridge...${RESET}\n"; \
		   $(DC) exec mqtt-bridge bash ;; \
		*) printf "${RED}Invalid choice. Please select 1, 2, or 3.${RESET}\n"; \
		   exit 1 ;; \
	esac

reset-db:
	$(HIDE)printf "$(ORANGE)Resetting database – removing volumes and recreating containers...\n$(RESET)"
	$(HIDE)$(DC) down -v
	$(HIDE)$(DC) up -d

# ================================================= #
#             4. HARDWARE / PLATFORMIO              #
# ================================================= #
hw-build:
	$(HIDE)printf "$(BLUE)Building $(PROJECT_NAME) hardware firmware...\n$(RESET)"
	$(PIO)

upload:
	$(HIDE)printf "$(GREEN)Uploading $(PROJECT_NAME) firmware to the physical ESP32 board...\n$(RESET)"
	$(PIO) --target upload

monitor:
	$(HIDE)printf "$(CYAN)Starting $(PROJECT_NAME) serial monitor...\n$(RESET)"
	$(PIO) --target monitor

hw-clean:
	$(HIDE)printf "$(RED)Cleaning $(PROJECT_NAME) hardware build artifacts...\n$(RESET)"
	$(PIO) --target clean --verbose
	$(HIDE)rm -rf $(HARDWARE_DIR)/.pio

# ================================================= #
#                5. DOCUMENTATION                   #
# ================================================= #

help:
	$(HIDE)printf "\n${BLUE}IAS42 Makefile${RESET}\n"
	$(HIDE)printf "  Software, hardware, Docker and SSL deployment management.\n"
	$(HIDE)printf "\n${WHITE}COMMANDS${RESET}\n"
	$(HIDE)printf "  ${GREEN}build${RESET}      Build the selected stack.\n"
	$(HIDE)printf "  ${RED}clean${RESET}      Clean selected runtime/build artifacts.\n"
	$(HIDE)printf "  ${GREEN}rebuild${RESET}    Rebuild the selected stack.\n"
	$(HIDE)printf "  ${BLUE}up${RESET}         Start the software stack.\n"
	$(HIDE)printf "  ${RED}down${RESET}       Stop the software stack.\n"
	$(HIDE)printf "  ${GREEN}reboot${RESET}     Restart the software stack.\n"
	$(HIDE)printf "  ${ORANGE}ssl${RESET}        Validate the installed SSL certificate/key.\n"
	$(HIDE)printf "  ${ORANGE}logs${RESET}       View container logs.\n"
	$(HIDE)printf "  ${YELLOW}shell${RESET}      Open a shell in a container.\n"
	$(HIDE)printf "  ${CYAN}upload${RESET}     Upload firmware to the physical ESP32 board.\n"
	$(HIDE)printf "  ${MAGENTA}monitor${RESET}    Open the physical ESP32 serial monitor.\n"
	$(HIDE)printf "  ${RED}reset-db${RESET}   Reset the database volume.\n"
	$(HIDE)printf "\n${WHITE}TARGETS${RESET}\n"
	$(HIDE)printf "  ${ORANGE}sw${RESET}         Software stack.\n"
	$(HIDE)printf "  ${CYAN}hw${RESET}         Hardware stack.\n"
	$(HIDE)printf "  ${ORANGE}both${RESET}       Software and hardware.\n"
	$(HIDE)printf "\n${GREY}Examples:${RESET}\n"
	$(HIDE)printf "  make build sw\n"
	$(HIDE)printf "  make build hw\n"
	$(HIDE)printf "  make build both\n"
	$(HIDE)printf "  make rebuild sw\n"
	$(HIDE)printf "  make ssl\n"

.PHONY: build up down reboot rebuild logs shell ssl clean sw hw both \
        sw-build sw-clean both-build both-clean both-rebuild \
        hw-build upload monitor hw-clean reset-db help
