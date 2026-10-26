//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_mutation.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatReceipt {
  /// Returns a new [GroupChatReceipt] instance.
  GroupChatReceipt({
    required this.recorded,

    required this.operationId,

    required this.receipt,
  });

  @JsonKey(name: r'recorded', required: true, includeIfNull: false)
  final bool recorded;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'receipt', required: true, includeIfNull: false)
  final GroupChatMutation receipt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatReceipt &&
          other.recorded == recorded &&
          other.operationId == operationId &&
          other.receipt == receipt;

  @override
  int get hashCode =>
      recorded.hashCode + operationId.hashCode + receipt.hashCode;

  factory GroupChatReceipt.fromJson(Map<String, dynamic> json) =>
      _$GroupChatReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
