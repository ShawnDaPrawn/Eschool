class ChatSettings {

  ChatSettings({
    required this.maxFilesOrImagesInOneMessage,
    required this.maxFileSizeInBytesCanBeSent,
    required this.maxCharactersInATextMessage,
    required this.automaticallyMessagesRemovedDays,
  });

  factory ChatSettings.fromJson(Map json) {
    return ChatSettings(
      maxFilesOrImagesInOneMessage: int.tryParse(
            json['max_files_or_images_in_one_message'].toString(),
          ) ??
          10,
      maxFileSizeInBytesCanBeSent:
          int.tryParse(json['max_file_size_in_bytes'].toString()) ?? 10000000,
      maxCharactersInATextMessage:
          int.tryParse(json['max_characters_in_text_message'].toString()) ??
              500,
      automaticallyMessagesRemovedDays: int.tryParse(
            json['automatically_messages_removed_days'].toString(),
          ) ??
          0,
    );
  }
  final int maxFilesOrImagesInOneMessage;
  final int maxFileSizeInBytesCanBeSent;
  final int maxCharactersInATextMessage;
  final int automaticallyMessagesRemovedDays;
}
