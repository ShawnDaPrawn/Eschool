//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'confirm_my_text_messaging_code_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConfirmMyTextMessagingCodeRequest {
  /// Returns a new [ConfirmMyTextMessagingCodeRequest] instance.
  ConfirmMyTextMessagingCodeRequest({required this.code});

  /// The six digits from the SMS.
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConfirmMyTextMessagingCodeRequest && other.code == code;

  @override
  int get hashCode => code.hashCode;

  factory ConfirmMyTextMessagingCodeRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$ConfirmMyTextMessagingCodeRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ConfirmMyTextMessagingCodeRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
