class ApiException implements Exception {
  ApiException(
    this.errorMessage, {
    this.isApiMessage = false,
    this.statusCode,
    this.isTransportFailure = false,
  });
  final int? statusCode;

  /// A connection/timeout failure without an HTTP response; never a refusal.
  final bool isTransportFailure;
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
