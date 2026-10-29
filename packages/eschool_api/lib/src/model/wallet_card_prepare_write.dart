//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_card_prepare_write_lines_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_card_prepare_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCardPrepareWrite {
  /// Returns a new [WalletCardPrepareWrite] instance.
  WalletCardPrepareWrite({
    required this.operationId,

    required this.cardId,

    required this.picc,

    required this.mac,

    required this.lines,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'card_id', required: true, includeIfNull: false)
  final String cardId;

  @JsonKey(name: r'picc', required: true, includeIfNull: false)
  final String picc;

  @JsonKey(name: r'mac', required: true, includeIfNull: false)
  final String mac;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<WalletCardPrepareWriteLinesInner> lines;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCardPrepareWrite &&
          other.operationId == operationId &&
          other.cardId == cardId &&
          other.picc == picc &&
          other.mac == mac &&
          other.lines == lines;

  @override
  int get hashCode =>
      operationId.hashCode +
      cardId.hashCode +
      picc.hashCode +
      mac.hashCode +
      lines.hashCode;

  factory WalletCardPrepareWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletCardPrepareWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletCardPrepareWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
