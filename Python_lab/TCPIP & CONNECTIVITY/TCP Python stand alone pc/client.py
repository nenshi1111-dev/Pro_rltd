import socket

def start_client(server_host='127.0.0.1', server_port=65432):
    # Create TCP/IP socket
    client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

    # Connect to server
    client_socket.connect((server_host, server_port))
    print(f"Connected to {server_host}:{server_port}")

    # Send message
    message = "Hello Server!"
    client_socket.sendall(message.encode())

    # Receive response
    response = client_socket.recv(1024).decode()
    print(f"Server says: {response}")

    client_socket.close()

if __name__ == "__main__":
    start_client()
