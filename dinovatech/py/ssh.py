#!/usr/bin/env python3
# dinovatech/py/ssh.py
# Execução remota SSH com Paramiko e suporte a chave RSA fornecida em memória via stdin.

import sys
import os
import argparse
import io

try:
    import paramiko
except ImportError:
    print("ERRO: A biblioteca 'paramiko' não está instalada no ambiente Python.", file=sys.stderr)
    sys.exit(1)

def main():
    parser = argparse.ArgumentParser(description="SSH Remote Deploy Tool for Dinovatech")
    parser.add_argument("--host", default="172.17.0.1", help="Host IP or hostname")
    parser.add_argument("--port", type=int, default=22, help="SSH Port")
    parser.add_argument("--user", default="root", help="SSH Username")
    parser.add_argument("--cmd", default="", help="Command to execute on remote host")
    parser.add_argument("--get-pubkey", action="store_true", help="Extract OpenSSH public key from private key in stdin")
    
    args = parser.parse_args()
    
    # Lê a chave privada PEM da entrada padrão (stdin) ou variável de ambiente
    key_pem = os.environ.get("SSH_KEY_PEM", "")
    if not key_pem:
        key_pem = sys.stdin.read()

    if not key_pem or not key_pem.strip():
        print("ERRO: Nenhuma chave RSA (.pem) foi fornecida.", file=sys.stderr)
        sys.exit(1)

    try:
        # Carrega a chave RSA em memória usando io.StringIO
        key_file = io.StringIO(key_pem.strip())
        rsa_key = paramiko.RSAKey.from_private_key(key_file)
    except Exception as e:
        print(f"ERRO ao carregar a chave RSA private key: {e}", file=sys.stderr)
        sys.exit(1)

    # Se o objetivo for apenas extrair a chave pública para a VPS
    if args.get_pubkey:
        pub_base64 = rsa_key.get_base64()
        print(f"ssh-rsa {pub_base64} dinovatech-deploy")
        sys.exit(0)

    if not args.cmd:
        print("ERRO: Nenhum comando (--cmd) especificado para execução.", file=sys.stderr)
        sys.exit(1)

    # Conecta via SSH ao host
    ssh = paramiko.SSHClient()
    ssh.load_system_host_keys()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    try:
        ssh.connect(
            hostname=args.host,
            port=args.port,
            username=args.user,
            pkey=rsa_key,
            timeout=15
        )
        
        stdin, stdout, stderr = ssh.exec_command(args.cmd)
        exit_code = stderr.channel.recv_exit_status()
        
        out_str = stdout.read().decode('utf-8', errors='replace')
        err_str = stderr.read().decode('utf-8', errors='replace')
        
        if out_str:
            print(out_str, end="")
        if err_str:
            print(err_str, end="", file=sys.stderr)
            
        sys.exit(exit_code)
        
    except Exception as e:
        print(f"ERRO de Conexão SSH [{args.user}@{args.host}:{args.port}]: {e}", file=sys.stderr)
        sys.exit(1)
    finally:
        ssh.close()

if __name__ == "__main__":
    main()
