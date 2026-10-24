class ApiException implements Exception {
  ApiException(this.errorMessage, {this.isApiMessage = false, this.statusCode});
  final int? statusCode;
  String errorMessage;
  bool
  isApiMessage; //if it's a message from api and not a code to be translated

  @override
  String toString() {
    return errorMessage;
  }

  factory ApiException.fromException(Object exception) {
    if (exception is ApiException) {
      return exception;
    } else {
      return ApiException(exception.toString());
    }
  }
}
