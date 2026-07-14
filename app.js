/*
 * Copyright IBM Corp. All Rights Reserved.
 *
 * SPDX-License-Identifier: Apache-2.0
 */

const grpc = require('@grpc/grpc-js');
const { connect, hash, signers } = require('@hyperledger/fabric-gateway');
const crypto = require('node:crypto');
const fs = require('node:fs/promises');
const path = require('node:path');
const { TextDecoder } = require('node:util');

const channelName = envOrDefault('CHANNEL_NAME', 'mychannel');
const chaincodeName = envOrDefault('CHAINCODE_NAME', 'worklog');
const mspId = envOrDefault('MSP_ID', 'Org1MSP');

// Path to crypto materials.
const cryptoPath = envOrDefault(
    'CRYPTO_PATH',
    path.resolve(
        __dirname,
        '..',
        '..',
        '..',
        'test-network',
        'organizations',
        'peerOrganizations',
        'org1.example.com'
    )
);

// Path to user private key directory.
const keyDirectoryPath = envOrDefault(
    'KEY_DIRECTORY_PATH',
    path.resolve(
        cryptoPath,
        'users',
        'User1@org1.example.com',
        'msp',
        'keystore'
    )
);

// Path to user certificate directory.
const certDirectoryPath = envOrDefault(
    'CERT_DIRECTORY_PATH',
    path.resolve(
        cryptoPath,
        'users',
        'User1@org1.example.com',
        'msp',
        'signcerts'
    )
);

// Path to peer tls certificate.
const tlsCertPath = envOrDefault(
    'TLS_CERT_PATH',
    path.resolve(cryptoPath, 'peers', 'peer0.org1.example.com', 'tls', 'ca.crt')
);

// Gateway peer endpoint.
const peerEndpoint = envOrDefault('PEER_ENDPOINT', 'localhost:7051');

// Gateway peer SSL host name override.
const peerHostAlias = envOrDefault('PEER_HOST_ALIAS', 'peer0.org1.example.com');

const utf8Decoder = new TextDecoder();
const attendanceId = `ATT${Date.now()}`;
const taskId = `TASK${Date.now()}`;
const verificationId = `VER${Date.now()}`;

async function main() {
    displayInputParameters();

    // The gRPC client connection should be shared by all Gateway connections to this endpoint.
    const client = await newGrpcConnection();

    const gateway = connect({
        client,
        identity: await newIdentity(),
        signer: await newSigner(),
        hash: hash.sha256,
        // Default timeouts for different gRPC calls
        evaluateOptions: () => {
            return { deadline: Date.now() + 5000 }; // 5 seconds
        },
        endorseOptions: () => {
            return { deadline: Date.now() + 15000 }; // 15 seconds
        },
        submitOptions: () => {
            return { deadline: Date.now() + 5000 }; // 5 seconds
        },
        commitStatusOptions: () => {
            return { deadline: Date.now() + 60000 }; // 1 minute
        },
    });

    try {
        // Get a network instance representing the channel where the smart contract is deployed.
        const network = gateway.getNetwork(channelName);

        // Get the smart contract from the network.
        const contract = network.getContract(chaincodeName);

        // Initialize a set of asset data on the ledger using the chaincode 'InitLedger' function.
        await initLedger(contract);

        await createAttendance(contract);
        await getAllAttendance(contract);
        await readAttendance(contract);

        await createTask(contract);
        await getAllTasks(contract);
        await readTask(contract);

        await createVerification(contract);
        await getAllVerification(contract);
        await readVerification(contract);
    } finally {
        gateway.close();
        client.close();
    }
}

main().catch((error) => {
    console.error('******** FAILED to run the application:', error);
    process.exitCode = 1;
});

async function newGrpcConnection() {
    const tlsRootCert = await fs.readFile(tlsCertPath);
    const tlsCredentials = grpc.credentials.createSsl(tlsRootCert);
    return new grpc.Client(peerEndpoint, tlsCredentials, {
        'grpc.ssl_target_name_override': peerHostAlias,
    });
}

async function newIdentity() {
    const certPath = await getFirstDirFileName(certDirectoryPath);
    const credentials = await fs.readFile(certPath);
    return { mspId, credentials };
}

async function getFirstDirFileName(dirPath) {
    const files = await fs.readdir(dirPath);
    const file = files[0];
    if (!file) {
        throw new Error(`No files in directory: ${dirPath}`);
    }
    return path.join(dirPath, file);
}

async function newSigner() {
    const keyPath = await getFirstDirFileName(keyDirectoryPath);
    const privateKeyPem = await fs.readFile(keyPath);
    const privateKey = crypto.createPrivateKey(privateKeyPem);
    return signers.newPrivateKeySigner(privateKey);
}

async function initLedger(contract) {
    console.log(
        '\n--> Submit Transaction: InitLedger, function creates the initial set of assets on the ledger'
    );

    await contract.submitTransaction('InitLedger');

    console.log('*** Transaction committed successfully');
}

async function createAttendance(contract) {

    console.log("\n--> CreateAttendance");

    await contract.submitTransaction(
        "CreateAttendance",
        attendanceId,
        "USR001",
        "2026-07-04",
        "08:00",
        "17:00"
    );

    console.log("*** Attendance berhasil dibuat");
}

async function getAllAttendance(contract){

    const result = await contract.evaluateTransaction(
        "GetAllAttendance"
    );

    console.log(
        JSON.parse(
            utf8Decoder.decode(result)
        )
    );

}

async function readAttendance(contract){

    const result = await contract.evaluateTransaction(
        "ReadAttendance",
        attendanceId
    );

    console.log(
        JSON.parse(
            utf8Decoder.decode(result)
        )
    );

}

async function createTask(contract){

    await contract.submitTransaction(
        "CreateTask",
        taskId,
        "USR001",
        "2026-07-04",
        "Mengerjakan Dashboard",
        "8",
        "HASH123456"
    );

    console.log("*** Task berhasil dibuat");

}

async function getAllTasks(contract){

    const result = await contract.evaluateTransaction(
        "GetAllTasks"
    );

    console.log(
        JSON.parse(
            utf8Decoder.decode(result)
        )
    );

}

async function readTask(contract){

    const result = await contract.evaluateTransaction(
        "ReadTask",
        taskId
    );

    console.log(
        JSON.parse(
            utf8Decoder.decode(result)
        )
    );

}

async function createVerification(contract){

    await contract.submitTransaction(
        "CreateVerification",
        verificationId,
        taskId,
        "ADMIN001",
        "Disetujui",
        "Semua sudah sesuai"
    );

    console.log("*** Verification berhasil dibuat");

}

async function getAllVerification(contract){

    const result = await contract.evaluateTransaction(
        "GetAllVerification"
    );

    console.log(
        JSON.parse(
            utf8Decoder.decode(result)
        )
    );

}

async function readVerification(contract){

    const result = await contract.evaluateTransaction(
        "ReadVerification",
        verificationId
    );

    console.log(
        JSON.parse(
            utf8Decoder.decode(result)
        )
    );

}

/**
 * envOrDefault() will return the value of an environment variable, or a default value if the variable is undefined.
 */
function envOrDefault(key, defaultValue) {
    return process.env[key] || defaultValue;
}

/**
 * displayInputParameters() will print the global scope parameters used by the main driver routine.
 */
function displayInputParameters() {
    console.log(`channelName:       ${channelName}`);
    console.log(`chaincodeName:     ${chaincodeName}`);
    console.log(`mspId:             ${mspId}`);
    console.log(`cryptoPath:        ${cryptoPath}`);
    console.log(`keyDirectoryPath:  ${keyDirectoryPath}`);
    console.log(`certDirectoryPath: ${certDirectoryPath}`);
    console.log(`tlsCertPath:       ${tlsCertPath}`);
    console.log(`peerEndpoint:      ${peerEndpoint}`);
    console.log(`peerHostAlias:     ${peerHostAlias}`);
}
