//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_quote.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_quote_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceQuoteResponse {
  /// Returns a new [CommerceQuoteResponse] instance.
  CommerceQuoteResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final CommerceQuote data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceQuoteResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory CommerceQuoteResponse.fromJson(Map<String, dynamic> json) =>
      _$CommerceQuoteResponseFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceQuoteResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
