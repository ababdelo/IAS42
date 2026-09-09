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
SSL_CERTS_DIR       := $(SOFTWARE_DIR)/server/ssl/certs
SSL_PRIVATE_DIR     := $(SOFTWARE_DIR)/server/ssl/private
CERT_FILE           := $(SSL_CERTS_DIR)/server.crt
KEY_FILE            := $(SSL_PRIVATE_DIR)/server.key

# Colors for terminal output
RESET       = \033[0m
WHITE       = \033[1;37m
GREY        = \033[1;90m
BLACK       = \033[1;30m
BROWN       = \033[1;38;5;88m
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
DC          = docker compose --env-file $(ENV_FILE) -f $(COMPOSE_FILE)
PIO         = ${HIDE} pio run -d $(HARDWARE_DIR)
PRINTF_     = ${HIDE}printf

.DEFAULT_GOAL := help

# ================================================= #
#             2. ROUTING & GLOBAL COMMANDS          #
# ================================================= #

# Dummy targets for routing arguments (e.g., "make build sw")
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
		esac; \
	fi

up:
	${PRINTF_} "$(GREEN)Starting $(PROJECT_NAME) containers...\n$(RESET)"
	${HIDE}$(DC) up -d

down:
	${PRINTF_} "$(YELLOW)Stopping and removing $(PROJECT_NAME) containers...\n$(RESET)"
	${HIDE}$(DC) down

reboot:
	${PRINTF_} "$(ORANGE)Restarting $(PROJECT_NAME) containers...\n$(RESET)"
	${HIDE}$(DC) restart

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
			*) printf "${RED}Invalid choice. Please select a valid option.${RESET}\n"; exit 1 ;; \
		esac; \
	fi; \
	case $$target in \
		sw) printf "${GREEN}Rebuilding Software...${RESET}\n"; $(MAKE) sw-clean sw-build ;; \
		hw) printf "${YELLOW}Rebuilding Hardware...${RESET}\n"; $(MAKE) hw-clean hw-build ;; \
		both) printf "${CYAN}Rebuilding Software and Hardware...${RESET}\n"; $(MAKE) both-rebuild ;; \
		*) printf "${RED}Invalid target. Please select sw, hw, or both.${RESET}\n"; exit 1 ;; \
	esac

# ================================================= #
#               3. SOFTWARE / DOCKER                #
# ================================================= #

sw-build: ssl
	${PRINTF_} "$(BLUE)Building and starting $(PROJECT_NAME) containers...\n$(RESET)"
	${HIDE}$(DC) up -d --build

sw-clean: down
	$(HIDE)printf "$(RED)WARNING: Wiping project containers, images, database volumes, and generated SSL certs!$(RESET)\n"
	${HIDE}$(DC) down -v --rmi all --remove-orphans
	${PRINTF_} "$(RED)Cleaning server logs and generated SSL certificates...\n$(RESET)"
	${HIDE}rm -f $(CERT_FILE) $(KEY_FILE) 2>/dev/null || true
	${HIDE}rm -rf $(SOFTWARE_DIR)/server/logs/apache/* $(SOFTWARE_DIR)/server/logs/application/* 2>/dev/null || true
	${PRINTF_} "$(GREEN)Cleanup completed successfully.\n$(RESET)"

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
	$(HIDE)if [ ! -f "$(CERT_FILE)" ]; then \
		printf "$(YELLOW)Generating self-signed SSL certificates...$(RESET)\n"; \
		mkdir -p $(SSL_CERTS_DIR) $(SSL_PRIVATE_DIR); \
		MSYS_NO_PATHCONV=1 openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
			-keyout $(KEY_FILE) -out $(CERT_FILE) \
			-subj "/C=MA/ST=Casablanca/L=Casablanca/O=ED42/OU=IAS42/CN=localhost" 2>/dev/null || { \
				rm -f $(CERT_FILE) $(KEY_FILE); \
				printf "$(RED)SSL certificate generation failed.$(RESET)\n"; \
				exit 1; \
		}; \
		printf "$(GREEN)SSL Certificates generated successfully!$(RESET)\n"; \
	else \
		printf "$(GREEN)SSL Certificates already exist. Skipping generation.$(RESET)\n"; \
	fi

logs:
	@printf "${CYAN}Choose a service to view logs:${RESET}\n"; \
	printf "${GREEN}1) Apache${RESET}\n"; \
	printf "${YELLOW}2) MySQL${RESET}\n"; \
	read -p "Enter your choice (1-2): " choice; \
	case $$choice in \
		1) printf "${GREEN}Viewing logs for Apache...${RESET}\n"; \
		   $(DC) logs -f apache ;; \
		2) printf "${YELLOW}Viewing logs for MySQL...${RESET}\n"; \
		   $(DC) logs -f mysql ;; \
		*) printf "${RED}Invalid choice. Please select a valid option.${RESET}\n"; \
	esac

shell:
	@printf "${CYAN}Choose a service to open a shell:${RESET}\n"; \
	printf "${GREEN}1) Apache${RESET}\n"; \
	printf "${YELLOW}2) MySQL${RESET}\n"; \
	read -p "Enter your choice (1-2): " choice; \
	case $$choice in \
		1) printf "${GREEN}Opening shell for Apache...${RESET}\n"; \
		   $(DC) exec apache bash ;; \
		2) printf "${YELLOW}Opening shell for MySQL...${RESET}\n"; \
		   $(DC) exec mysql bash ;; \
		*) printf "${RED}Invalid choice. Please select a valid option.${RESET}\n"; \
	esac

reset-db:
	$(HIDE)printf "$(ORANGE)Resetting database – removing volumes and recreating containers...$(RESET)\n"
	${HIDE}$(DC) down -v
	${HIDE}$(DC) up -d

# ================================================= #
#             4. HARDWARE / PLATFORMIO              #
# ================================================= #

hw-build:
	${PRINTF_} "$(BLUE)Building $(PROJECT_NAME) hardware firmware...\n$(RESET)"
	${PIO}

upload:
	${PRINTF_} "$(GREEN)Uploading $(PROJECT_NAME) firmware to the physical ESP32 board...\n$(RESET)"
	${PIO} --target upload

monitor:
	${PRINTF_} "$(CYAN)Starting $(PROJECT_NAME) serial monitor...\n$(RESET)"
	${PIO} --target monitor

hw-clean:
	${PRINTF_} "$(RED)Cleaning $(PROJECT_NAME) hardware build artifacts...\n$(RESET)"
	${PIO} --target clean --verbose
	$(HIDE) rm -rf $(HARDWARE_DIR)/.pio

# ================================================= #
#                5. DOCUMENTATION                   #
# ================================================= #

help:
	${PRINTF_} "\n${BLUE}IAS42 Makefile${RESET}\n"
	${PRINTF_} "  Simplified Commands for Managing the IAS42 Software and Hardware Stack.\n"
	${PRINTF_} "\n${WHITE}COMMANDS${RESET}\n"
	${PRINTF_} "  ${GREEN}build${RESET}      Build the selected stack.\n"
	${PRINTF_} "  ${RED}clean${RESET}      Remove build output and runtime artifacts.\n"
	${PRINTF_} "  ${GREEN}rebuild${RESET}    Rebuild the selected stack.\n"
	${PRINTF_} "  ${BLUE}up${RESET}         Start the software stack.\n"
	${PRINTF_} "  ${RED}down${RESET}       Stop the software stack.\n"
	${PRINTF_} "  ${GREEN}reboot${RESET}     Restart the software stack.\n"
	${PRINTF_} "  ${ORANGE}logs${RESET}       View container logs.\n"
	${PRINTF_} "  ${YELLOW}shell${RESET}      Open a shell in a container.\n"
	${PRINTF_} "  ${CYAN}upload${RESET}     Upload firmware to the physical ESP32 board.\n"
	${PRINTF_} "  ${MAGENTA}monitor${RESET}    Open the physical ESP32 serial monitor.\n"
	${PRINTF_} "  ${RED}reset-db${RESET}   Reset the database (remove volume and restart).\n"
	${PRINTF_} "\n${WHITE}OPTIONS${RESET}\n"
	${PRINTF_} "  ${ORANGE}sw${RESET}         Software stack.\n"
	${PRINTF_} "  ${CYAN}hw${RESET}         Hardware stack.\n"
	${PRINTF_} "  ${ORANGE}both${RESET}       For both stacks.\n"	
	${PRINTF_} "  ${CYAN}Apache${RESET}     Apache container for logs or shell.\n"
	${PRINTF_} "  ${ORANGE}MySQL${RESET}      MySQL container for logs or shell.\n"
	${PRINTF_} "\n${GREY}Use 'make build sw', 'make build hw', 'make build both', 'make clean sw', 'make clean hw', 'make clean both', 'make rebuild sw', 'make rebuild hw', or 'make rebuild both'.${RESET}\n"

.PHONY: build up down reboot rebuild logs shell ssl clean sw hw both sw-build sw-clean both-build both-clean both-rebuild hw-build \
        upload monitor hw-clean reset-db help
