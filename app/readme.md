# Instrucciones para levantar la app

# 1. Construir imagen (desde /app)
docker build -t angeltun/app-k8s:v1 .

# 2. Probar que funcione el index de forma local
docker run --rm -p 8080:80 angeltun/app-k8s:v1
# 3. abrir http://localhost:8080/

# 4. Subir a Docker Hub
docker login
docker push angeltun/app-k8s:v1

# 5. Para probar en Kubernetes estando dentro de la carpeta k8s
kubectl apply -f configmap.yaml
kubectl apply -f deployment.yaml
kubectl apply -f service.yaml

# 6. verificar que los pods estan corriendo
kubectl get pods -l app=webapp
kubectl get svc webapp-service


# 7. Acceder al NodePort desde tu máquina con el port forward:
kubectl port-forward svc/webapp-service 8080:80
kubectl port-forward svc/webapp-service 30710:80

# 8. abrir http://localhost:8080/


# extra "borrar pods"
kubectl delete -f deployment.yaml
kubectl delete -f service.yaml
kubectl delete -f configmap.yaml