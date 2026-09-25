import socket

def start_server(host='127.0.0.1', port=65432):
    # Create TCP/IP socket
    server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

    # Bind to address and port
    server_socket.bind((host, port))

    # Start listening
    server_socket.listen()
    print(f"Server listening on {host}:{port}")

    while True:
        conn, addr = server_socket.accept()
        print(f"Connected by {addr}")

        # Receive data
        data = conn.recv(1024).decode()
        if not data:
            break
        print(f"Client says: {data}")

        # Send reply
        conn.sendall("Message received".encode())

        conn.close()

if __name__ == "__main__":
    start_server()
