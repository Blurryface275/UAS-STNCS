/*
 * Copyright IBM Corp. All Rights Reserved.
 *
 * SPDX-License-Identifier: Apache-2.0
 */

'use strict';

// Deterministic JSON.stringify()
const stringify = require('json-stringify-deterministic');
const sortKeysRecursive = require('sort-keys-recursive');
const { Contract } = require('fabric-contract-api');

class WFHMonitoringContract extends Contract {

    async InitLedger(ctx) {

        const assets = [

            {
                ID: 'ATT001',
                UserID: 'USR001',
                Tanggal: '2026-07-01',
                ClockIn: '08:00',
                ClockOut: '17:00',
                docType: 'attendance',
            },

            {
                ID: 'TASK001',
                UserID: 'USR001',
                Tanggal: '2026-07-01',
                Aktivitas: 'Meeting Tim',
                Durasi: 2,
                FileHash: '',
                docType: 'task',
            },

            {
                ID: 'VER001',
                TaskID: 'TASK001',
                VerifierID: 'USR002',
                Status: 'Pending',
                Catatan: '',
                docType: 'verification',
            }

        ];

        for (const asset of assets) {

            await ctx.stub.putState(
                asset.ID,
                Buffer.from(stringify(sortKeysRecursive(asset)))
            );

        }
    }

    //attendance (kehadiran)
    // CreateAsset issues a new asset to the world state with given details.
    async CreateAttendance(ctx, id, userID, tanggal, clockIn, clockOut) {

        const exists = await this.AttendanceExists(ctx, id);

        if (exists) {
            throw new Error(`Attendance ${id} sudah ada`);
        }

        const attendance = {
            ID: id,
            UserID: userID,
            Tanggal: tanggal,
            ClockIn: clockIn,
            ClockOut: clockOut,
            docType: "attendance",
        };

        await ctx.stub.putState(
            id,
            Buffer.from(stringify(sortKeysRecursive(attendance)))
        );

        return JSON.stringify(attendance);
    }

    // ReadAsset returns the asset stored in the world state with given id.
    async ReadAttendance(ctx, id) {

        const attendanceJSON = await ctx.stub.getState(id);

        if (!attendanceJSON || attendanceJSON.length === 0) {
            throw new Error(`Attendance ${id} tidak ditemukan`);
        }

        return attendanceJSON.toString();
    }

    // UpdateAsset updates an existing asset in the world state with provided parameters.
    async UpdateAttendance(ctx, id, clockOut) {

        const attendanceString = await this.ReadAttendance(ctx, id);

        const attendance = JSON.parse(attendanceString);

        attendance.ClockOut = clockOut;

        await ctx.stub.putState(
            id,
            Buffer.from(stringify(sortKeysRecursive(attendance)))
        );

        return JSON.stringify(attendance);
    }

    // DeleteAsset deletes an given asset from the world state.
    async DeleteAttendance(ctx, id) {

        const exists = await this.AttendanceExists(ctx, id);

        if (!exists) {
            throw new Error(`Attendance ${id} tidak ditemukan`);
        }

        await ctx.stub.deleteState(id);
    }

    // AssetExists returns true when asset with given ID exists in world state.
    async AttendanceExists(ctx, id) {

        const attendanceJSON = await ctx.stub.getState(id);

        return attendanceJSON && attendanceJSON.length > 0;
    }

    // GetAllAssets returns all assets found in the world state.
    async GetAllAttendance(ctx) {

        const allResults = [];

        const iterator = await ctx.stub.getStateByRange('', '');

        let result = await iterator.next();

        while (!result.done) {

            const strValue = Buffer.from(result.value.value.toString()).toString('utf8');

            let record;

            try {
                record = JSON.parse(strValue);
            } catch (err) {
                record = strValue;
            }

            if (record.docType === "attendance") {
                allResults.push(record);
            }

            result = await iterator.next();
        }

        return JSON.stringify(allResults);
    }

    //task
    async CreateTask(ctx, id, userID, tanggal, aktivitas, durasi, fileHash) {

        const exists = await this.TaskExists(ctx, id);

        if (exists) {
            throw new Error(`Task ${id} sudah ada`);
        }

        const task = {
            ID: id,
            UserID: userID,
            Tanggal: tanggal,
            Aktivitas: aktivitas,
            Durasi: Number(durasi),
            FileHash: fileHash,
            docType: "task",
        };

        await ctx.stub.putState(
            id,
            Buffer.from(stringify(sortKeysRecursive(task)))
        );

        return JSON.stringify(task);
    }

    async ReadTask(ctx, id) {

        const taskJSON = await ctx.stub.getState(id);

        if (!taskJSON || taskJSON.length === 0) {
            throw new Error(`Task ${id} tidak ditemukan`);
        }

        return taskJSON.toString();
    }

    async UpdateTask(ctx, id, aktivitas, durasi) {

        const taskString = await this.ReadTask(ctx, id);

        const task = JSON.parse(taskString);

        task.Aktivitas = aktivitas;
        task.Durasi = Number(durasi);

        await ctx.stub.putState(
            id,
            Buffer.from(stringify(sortKeysRecursive(task)))
        );

        return JSON.stringify(task);
    }

    async DeleteTask(ctx, id) {

        const exists = await this.TaskExists(ctx, id);

        if (!exists) {
            throw new Error(`Task ${id} tidak ditemukan`);
        }

        await ctx.stub.deleteState(id);
    }

    async TaskExists(ctx, id) {

        const taskJSON = await ctx.stub.getState(id);

        return taskJSON && taskJSON.length > 0;
    }

    async GetAllTasks(ctx) {

        const allResults = [];

        const iterator = await ctx.stub.getStateByRange('', '');

        let result = await iterator.next();

        while (!result.done) {

            const strValue = Buffer.from(result.value.value.toString()).toString('utf8');

            let record;

            try {
                record = JSON.parse(strValue);
            } catch (err) {
                record = strValue;
            }

            if (record.docType === "task") {
                allResults.push(record);
            }

            result = await iterator.next();
        }

        return JSON.stringify(allResults);
    }

    //verification
    async CreateVerification(ctx, id, taskID, verifierID, status, catatan) {

        const exists = await this.VerificationExists(ctx, id);

        if (exists) {
            throw new Error(`Verification ${id} sudah ada`);
        }

        const verification = {
            ID: id,
            TaskID: taskID,
            VerifierID: verifierID,
            Status: status,
            Catatan: catatan,
            docType: "verification",
        };

        await ctx.stub.putState(
            id,
            Buffer.from(stringify(sortKeysRecursive(verification)))
        );

        return JSON.stringify(verification);
    }

    async ReadVerification(ctx, id) {

        const verificationJSON = await ctx.stub.getState(id);

        if (!verificationJSON || verificationJSON.length === 0) {
            throw new Error(`Verification ${id} tidak ditemukan`);
        }

        return verificationJSON.toString();
    }

    async UpdateVerification(ctx, id, status, catatan) {

        const verificationString = await this.ReadVerification(ctx, id);

        const verification = JSON.parse(verificationString);

        verification.Status = status;
        verification.Catatan = catatan;

        await ctx.stub.putState(
            id,
            Buffer.from(stringify(sortKeysRecursive(verification)))
        );

        return JSON.stringify(verification);
    }

    async DeleteVerification(ctx, id) {

        const exists = await this.VerificationExists(ctx, id);

        if (!exists) {
            throw new Error(`Verification ${id} tidak ditemukan`);
        }

        await ctx.stub.deleteState(id);
    }

    async VerificationExists(ctx, id) {

        const verificationJSON = await ctx.stub.getState(id);

        return verificationJSON && verificationJSON.length > 0;
    }

    async GetAllVerification(ctx) {

        const allResults = [];

        const iterator = await ctx.stub.getStateByRange('', '');

        let result = await iterator.next();

        while (!result.done) {

            const strValue = Buffer.from(result.value.value.toString()).toString('utf8');

            let record;

            try {
                record = JSON.parse(strValue);
            } catch (err) {
                record = strValue;
            }

            if (record.docType === "verification") {
                allResults.push(record);
            }

            result = await iterator.next();
        }

        return JSON.stringify(allResults);
    }

}

module.exports = WFHMonitoringContract;
